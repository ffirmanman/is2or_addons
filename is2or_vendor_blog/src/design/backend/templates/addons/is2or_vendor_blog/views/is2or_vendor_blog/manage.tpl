{**
 * Vendor Blog — manage.tpl
 * Blog post list for vendor panel.
 * Reuses the admin pages/manage.tpl by including it directly.
 * The controller already restricts $pages to this vendor's posts only.
 *}

{** Re-use the exact same admin template — no duplication needed **}
{include file="views/pages/manage.tpl"}
