<h4>Auto-Complete Orders</h4>
<p>Sample cron script that runs <strong>every day at 14:00</strong>:</p>
<pre>0 14 * * * php {$config.dir.root}/{$config.admin_index} --dispatch=is2or_vendor_payout.orders_cron</pre>