{if $is2or_similar_products}
    {include file="addons/is2or_ai_integration/similar_products.tpl" products=$is2or_similar_products}
{/if}
{if $is2or_also_bought_products}
    {include file="addons/is2or_ai_integration/also_bought_products.tpl" products=$is2or_also_bought_products}
{/if}