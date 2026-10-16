<?php

$schema['central']['products']['items']['is2or_vendor_blog.blog'] = [
    'href' => 'pages.manage?get_tree=multi_level&page_type=' . PAGE_TYPE_BLOG,
    'alt' => 'pages.manage?page_type=' . PAGE_TYPE_BLOG . ',pages.update?come_from=' . PAGE_TYPE_BLOG . ',pages.add?come_from=' . PAGE_TYPE_BLOG,
    'position' => 301
];

return $schema;