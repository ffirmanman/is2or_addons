<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Labels - Torod Shipping</title>
    <style>
        body { margin: 0; padding: 0; background-color: #525659; font-family: sans-serif; }
        .print-container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .label-block {
            margin-bottom: 30px;
            background: #fff;
            padding: 10px;
            border-radius: 4px;
        }
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            z-index: 1000;
        }
        @media print {
            .back-btn { display: none; }
            body { background: none; }
            .print-container { padding: 0; max-width: 100%; }
            .label-block {
                margin: 0;
                padding: 0;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        {foreach from=$awb_urls item="url"}
            <div class="label-block">
                <embed src="{$url}" type="application/pdf" width="100%" height="1100px" />
            </div>
        {/foreach}
    </div>

    <script type="text/javascript">
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1500);
        };
    </script>
</body>
</html>
