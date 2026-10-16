<p>{__("is2or_size_charts.chart_applies_text")}</p>

{include file="common/subheader.tpl" title=__("is2or_size_charts.brands")}
{include
file="addons/is2or_size_charts/pickers/brands/picker.tpl"
data_id="added_brands"
input_name="data[applies][brands]"
no_item_text=__("is2or_size_charts.all_brands")
item_ids=$data.applies.brands
type="links"
company_id=''
placement="right"
}

{include file="common/subheader.tpl" title=__("categories")}
{include
    file="pickers/categories/picker.tpl"
    company_ids=$picker_selected_companies
    multiple=true
    input_name="data[applies][categories]"
    item_ids=$data.applies.categories
    data_id="category_ids_`$id`"
    no_item_text=__("is2or_size_charts.all_categories")
    use_keys="N"
    owner_company_id=''
    but_meta="pull-right"
}

{include file="common/subheader.tpl" title=__("products")}
{include
    file="pickers/products/picker.tpl"
    data_id="added_products"
    input_name="data[applies][products]"
    no_item_text=__("text_no_items_defined", ["[items]" => __("products")])
    item_ids=$data.applies.products
    type="links"
    company_id=''
    placement="right"
}

{include file="common/subheader.tpl" title=__("vendors")}
{include file="pickers/companies/picker.tpl"
show_add_button=true
multiple=true
item_ids=$data.applies.vendors
view_mode="list"
input_name="data[applies][vendors]"
checkbox_name="data[applies][vendors]"
no_item_text=__("all_companies")
}

