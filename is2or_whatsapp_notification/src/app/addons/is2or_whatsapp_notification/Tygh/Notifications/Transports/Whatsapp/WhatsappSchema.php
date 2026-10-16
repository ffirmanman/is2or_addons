<?php

namespace Tygh\Notifications\Transports\Whatsapp;

use Tygh\Notifications\Transports\BaseMessageSchema;

class WhatsappSchema extends BaseMessageSchema
{
    /**
     * @var array|string
     */
    public $to;

    /**
     * @var array|string
     */
    public $from;

    /**
     * @var array|null|callable
     */
    public $data_modifier = null;

    /**
     * @var string
     */
    public $template_code;

    /**
     * @var string
     */
    public $legacy_template;

    /**
     * @var string
     */
    public $language_code;

    /**
     * A company that sends a message
     *
     * @var int|null
     */
    public $company_id;

    /**
     * A company that receives a message
     *
     * @var int|null
     */
    public $to_company_id;

    /**
     * @var string
     */
    public $area;

    /**
     * @var int
     */
    public $storefront_id;

    /**
     * @var int
     */
    public $to_storefront_id;

    public static function create(array $schema)
    {
        $self = new self();

        $self->to = self::get($schema, 'to');
        $self->from = self::get($schema, 'from');
        $self->data_modifier = self::get($schema, 'data_modifier');
        $self->template_code = self::get($schema, 'template_code');
        $self->legacy_template = self::get($schema, 'legacy_template');
        $self->language_code = self::get($schema, 'language_code');
        $self->company_id = self::get($schema, 'company_id');
        $self->to_company_id = self::get($schema, 'to_company_id');
        $self->area = self::get($schema, 'area');
        $self->storefront_id = self::get($schema, 'storefront_id');
        $self->to_storefront_id = self::get($schema, 'to_storefront_id');

        //TODO validate schema

        return $self;
    }
}
