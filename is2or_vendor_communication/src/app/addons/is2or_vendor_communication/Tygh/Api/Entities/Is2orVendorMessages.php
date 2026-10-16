<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Common\OperationResult;

class Is2orVendorMessages extends AEntity
{
    /**
     * Show chat list (GET /api/is2or_vendor_messages)
     */
    public function index($id = '', $params = array())
    {
        if (empty($this->auth['user_id'])) {
            return array('status' => Response::STATUS_UNAUTHORIZED);
        }

        \fn_load_addon('vendor_communication');

        $default_params = [
            'page'             => 1,
            'items_per_page'   => 10,
            'get_company_data' => true,
        ];

        $params = array_merge($default_params, (array) $params);

        $params['user_id']             = (int) $this->auth['user_id'];
        $params['communication_type']  = 'VENDOR_TO_CUSTOMER';
        $params['items_per_page'] = min((int)$params['items_per_page'], 500);
        $request_get_object_data = !empty($params['get_object_data']);
        $params['get_object_data'] = true;
        
        
        try {
            if ($id !== '') {
                $thread_data = db_get_row(
                    "SELECT thread_id, user_id, company_id, object_type, object_id, last_message_user_id
                    FROM ?:vendor_communications 
                    WHERE thread_id = ?i", 
                    $id
                );

                if (empty($thread_data)) {
                    return array('status' => Response::STATUS_NOT_FOUND);
                }

                // Prepare parameters for the message fetching function
                $params['thread_id'] = $id;

                // Fetch messages using the specific function
                $messages = \fn_vendor_communication_get_thread_messages($params);

                // Only set viewed if last message user is not current user
                if ($thread_data['last_message_user_id'] != $this->auth['user_id']) {
                    \fn_vendor_communication_mark_thread_as_viewed($thread_data);
                }
                
                return array(
                    'status' => Response::STATUS_OK,
                    'data'   => [
                        'thread'   => $thread_data,
                        'messages' => array_values($messages)
                    ]
                );
            } else {
                // 1. Get data threads
                list($threads, $search) = \fn_vendor_communication_get_threads($params, $params['items_per_page']);

                // 2. Loop for add image URL
                foreach ($threads as &$thread) {
                    $main_pair = $thread['object']['main_pair'] ?? null;

                    $thread['object_image_url'] = $main_pair
                        ? ($main_pair['detailed']['image_path']
                            ?? $main_pair['icon']['image_path']
                            ?? '')
                        : '';

                    if (!$request_get_object_data) {
                        unset($thread['object']);
                    }
                }
                unset($thread);
                $search['get_object_data'] = (bool) $request_get_object_data;
                
                return array(
                    'status' => Response::STATUS_OK,
                    'data' => [
                        'threads' => array_values($threads),
                        'params'  => $search
                    ]
                );
            }
            
        } catch (\Exception $e) {
            return array('status' => Response::STATUS_INTERNAL_SERVER_ERROR, 'data' => ['msg' => $e->getMessage()]);
        }
    }

    /**
     * Create a new conversation or reply to an existing one
     * (POST /api/is2or_vendor_messages)
     */
    public function create($params)
    {
        // Authorization check
        if (empty($this->auth['user_id'])) {
            return ['status' => Response::STATUS_UNAUTHORIZED];
        }

        \fn_load_addon('vendor_communication');
        $auth_user_id = (int) $this->auth['user_id'];

        // Message validation
        if (empty($params['message'])) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => ['message' => 'Message is required']
            ];
        }

        $result = new \Tygh\Common\OperationResult(false);
        $final_thread_id = 0;

        /**
         * 1. REPLY LOGIC
         * Reply to an existing thread using explicit thread_id
         */
        if (!empty($params['thread_id'])) {
           $final_thread_id = (int) $params['thread_id'];

            // Fetch minimal thread data safely
            $thread = db_get_row(
                "SELECT thread_id, user_id
                FROM ?:vendor_communications
                WHERE thread_id = ?i",
                $final_thread_id
            );

            if (empty($thread)) {
                return [
                    'status' => Response::STATUS_BAD_REQUEST,
                    'data'   => ['message' => 'Thread not found']
                ];
            }

            // Ownership check (admin bypass)
            if ($this->auth['user_type'] !== 'A'
                && (int) $thread['user_id'] !== $auth_user_id
            ) {
                return [
                    'status' => Response::STATUS_UNAUTHORIZED,
                    'data'   => ['message' => 'Access permission denied']
                ];
            }

            $message_data = [
                'thread_id' => $final_thread_id,
                'message'   => $params['message'],
                'user_id'   => $auth_user_id,
                'user_type' => $this->auth['user_type'],
            ];

            $result = \fn_vendor_communication_add_thread_message($message_data);
        }

        /**
         * 2. START NEW / AUTO-DETECT LOGIC
         * Create or reuse a thread based on company_id (and optional product_id)
         */
        elseif (!empty($params['company_id'])) {
            $company_id  = (int) $params['company_id'];
            $product_id  = !empty($params['product_id']) ? (int) $params['product_id'] : 0;
            $order_id    = !empty($params['order_id']) ? (int) $params['order_id'] : 0;

            // Define object type and ID object
            if ($order_id) {
                $object_type = 'O'; // O for Order
                $object_id   = $order_id;

                // VALIDATION: Make sure order exist and owned by user
                $order_exists = db_get_field(
                    "SELECT order_id FROM ?:orders WHERE order_id = ?i AND user_id = ?i AND company_id = ?i",
                    $order_id, $auth_user_id, $company_id
                );

                if (!$order_exists) {
                    return [
                        'status' => Response::STATUS_BAD_REQUEST,
                        'data'   => ['message' => 'Invalid order or order does not belong to you']
                    ];
                }
            } elseif ($product_id) {
                $object_type = 'P'; // P for Product
                $object_id   = $product_id;
            } else {
                $object_type = 'C'; // C for Company/Vendor
                $object_id   = $company_id;
            }

            // Check if a similar thread already exists (Logic tetap sama)
            $existing_thread_id = db_get_field(
                "SELECT thread_id
                FROM ?:vendor_communications
                WHERE company_id = ?i
                AND user_id = ?i
                AND object_id = ?i
                AND object_type = ?s",
                $company_id,
                $auth_user_id,
                $object_id,
                $object_type
            );

            if ($existing_thread_id) {
                // Reply to existing conversation
                $final_thread_id = (int) $existing_thread_id;

                $message_data = [
                    'thread_id' => $final_thread_id,
                    'message'   => $params['message'],
                    'user_id'   => $auth_user_id,
                    'user_type' => $this->auth['user_type'],
                ];

                $result = \fn_vendor_communication_add_thread_message($message_data);
            } else {
                // Create a new conversation
                // Validate vendor existence
                if (!db_get_field(
                    "SELECT company_id FROM ?:companies WHERE company_id = ?i AND status = 'A'",
                    $company_id
                )) {
                    return [
                        'status' => Response::STATUS_BAD_REQUEST,
                        'data'   => ['message' => 'Invalid vendor']
                    ];
                }

                $thread_data = [
                    'company_id'         => $company_id,
                    'user_id'            => $auth_user_id,
                    'user_type'          => $this->auth['user_type'],
                    'message'            => $params['message'],
                    'communication_type' => 'VENDOR_TO_CUSTOMER',
                    'object_type'        => $object_type,
                    'object_id'          => $object_id,
                ];

                $result = \fn_vendor_communication_create_thread($thread_data);
                
                if ($result->isSuccess()) {
                    $final_thread_id = (int) $result->getData();
                }
            }
        } else {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => ['message' => 'Missing identifiers']
            ];
        }

        // Final response
        if ($final_thread_id > 0 && $result->isSuccess()) {
            return [
                'status' => Response::STATUS_CREATED,
                'data'   => [
                    'thread_id' => $final_thread_id,
                    'message'   => 'Success'
                ]
            ];
        }

        return [
            'status' => Response::STATUS_BAD_REQUEST,
            'data'   => ['message' => 'Process failed']
        ];
    }

    /**
     * Mark thread(s) as viewed (PUT /api/Is2or/vendor_messages/<id>)
     * * This method handles both single and multiple thread updates.
     * For multiple threads, send an array 'thread_ids' in the request body.
     */
    public function update($id, $params)
    {
        // 1. Authorization check
        if (empty($this->auth['user_id'])) {
            return array('status' => Response::STATUS_UNAUTHORIZED);
        }

        \fn_load_addon('vendor_communication');
        $auth_user_id = (int) $this->auth['user_id'];
        $user_type = $this->auth['user_type'];

        // 2. Validate Status first
        $allowed_statuses = ['N', 'V', 'D'];
        if (empty($params['status']) || !in_array($params['status'], $allowed_statuses)) {
            return array(
                'status' => Response::STATUS_BAD_REQUEST, 
                'data'   => ['message' => 'No valid status type provided']
            );
        }

        // 3. Check for "Mark All" flag
        $mark_all = (isset($params['thread_ids']) && ($params['thread_ids'] === 'all' || in_array('all', $params['thread_ids'])));

        if ($mark_all) {
            // Handle "Mark All" logic
            db_query(
                "UPDATE ?:vendor_communications 
                SET status = ?s 
                WHERE (user_id = ?i OR ?s = 'A')", 
                $params['status'], 
                $auth_user_id,
                $user_type
            );

            $response_data = [
                'message' => 'All threads updated',
                'status'  => $params['status']
            ];
        } else {
            // 4. Handle Specific IDs logic (Move sanitization here)
            $thread_ids = !empty($params['thread_ids']) && is_array($params['thread_ids']) 
                ? $params['thread_ids'] 
                : [(int) $id];

            $thread_ids = array_filter(array_map('intval', $thread_ids));

            if (empty($thread_ids)) {
                return array(
                    'status' => Response::STATUS_BAD_REQUEST, 
                    'data'   => ['message' => 'No valid thread IDs provided']
                );
            }

            db_query(
                "UPDATE ?:vendor_communications 
                SET status = ?s 
                WHERE thread_id IN (?n) AND (user_id = ?i OR ?s = 'A')", 
                $params['status'], 
                $thread_ids, 
                $auth_user_id,
                $user_type
            );

            $response_data = [
                'thread_updated' => array_values($thread_ids),
                'total_updated'  => count($thread_ids),
                'status'         => $params['status']
            ];
        }

        // 5. Success response
        return array(
            'status' => Response::STATUS_OK,
            'data'   => $response_data
        );
    }

    public function delete($id) { return array('status' => Response::STATUS_METHOD_NOT_ALLOWED); }

    public function privileges() { return ['index' => true, 'create' => true, 'show' => true, 'update' => true]; }
    public function privilegesCustomer() { return ['index' => true, 'create' => true, 'show' => true, 'update' => true]; }
}