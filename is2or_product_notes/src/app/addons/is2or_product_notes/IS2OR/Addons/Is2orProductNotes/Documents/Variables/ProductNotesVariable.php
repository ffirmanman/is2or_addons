<?php
namespace IS2OR\Addons\Is2orProductNotes\Documents\Variables;

use Tygh\Template\IVariable;
use Tygh\Template\Snippet\Table\ItemContext;

class ProductNotesVariable implements IVariable {
    public $notes;

    public function __construct(ItemContext $context) {
        $item = $context->getItem();
        $this->notes = fn_product_notes_get_notes($item['item_id'], 'order', $item['order_id']);
    }
}
