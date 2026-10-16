<p>Here the sample cron script which run for <strong>every day at 14:00</strong>:</p>
<pre>0 14 * * * php {$config.dir.root}/{$config.admin_index} --dispatch=cart_reminder.cron</pre>