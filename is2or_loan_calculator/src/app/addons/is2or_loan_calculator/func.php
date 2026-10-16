<?php

function is2or_loan_calculator_get_filter_id () {

    return db_get_field("SELECT `filter_id` FROM ?:product_filters WHERE field_type = 'P' AND status = 'A' ORDER BY filter_id LIMIT 1");
}