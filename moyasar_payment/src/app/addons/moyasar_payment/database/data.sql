-- Add/Edit payment_processors
SET @mfOldProcessorId=(SELECT `processor_id` FROM `cscart_payment_processors` WHERE `processor_script`='moyasar_payment_processor.php' ORDER BY `processor_id` ASC LIMIT 1);
SET @mfLstProcessorId=(SELECT `processor_id` FROM `cscart_payment_processors` ORDER BY `processor_id` DESC LIMIT 1)+1;
SET @mfNewProcessorId=IFNULL(@mfOldProcessorId, @mfLstProcessorId);

REPLACE INTO `cscart_payment_processors`
(`processor_id`, `processor`, `processor_script`, `processor_template`, `admin_template`, `callback`, `type`, `addon`) VALUES
(@mfNewProcessorId, 'moyasar', 'moyasar_payment_processor.php',
        'addons/moyasar_payment/views/orders/components/payments/moyasar_payment.tpl', '', 'N', 'P', '');


-- -----------------------------------------------------------------------------
-- Add/Edit payments
SET @mfProcessorId=(SELECT `processor_id` FROM `cscart_payment_processors` WHERE `processor_script`='moyasar_payment_processor.php' ORDER BY `processor_id` ASC LIMIT 1);


SET @mfOldPaymentId=(SELECT `payment_id` FROM `cscart_payments` WHERE `processor_id`=@mfProcessorId ORDER BY `payment_id` ASC LIMIT 1);
SET @mfLstPaymentId=(SELECT `payment_id` FROM `cscart_payments` ORDER BY `payment_id` DESC LIMIT 1)+1;
SET @mfNewPaymentId=IFNULL(@mfOldPaymentId, @mfLstPaymentId);


REPLACE INTO `cscart_payments`
(`payment_id`, `processor_id`, `status`, `template`) VALUES
(@mfNewPaymentId, @mfProcessorId, 'D', 'addons/moyasar_payment/views/orders/components/payments/moyasar_payment.tpl');


-- -----------------------------------------------------------------------------
-- Add/Edit payment_descriptions
SET @mfPaymentId=(SELECT `payment_id` FROM `cscart_payments` WHERE `processor_id`=@mfProcessorId ORDER BY `payment_id` ASC LIMIT 1);

REPLACE INTO cscart_payment_descriptions (`payment`, `description`, `surcharge_title`, `lang_code`, `payment_id`) VALUES
('Moyasar', 'Pay with Moyasar', '', 'en', @mfPaymentId);
