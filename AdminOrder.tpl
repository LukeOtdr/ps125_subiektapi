<fieldset style="width:990px;margin-top:10px;height:250px;">
<legend>Status zamówienia w SubiektGT</legend>
<div style="float:left;width:50%">
<div>	Zamówienie: 
	{if $gtState.gt_order_sent == 1} 
			<span style="font-weight: bold;color:green;">TAK</span> nr zam.: <b>{$gtState.gt_order_ref}</b>
		{else} <span style="font-weight: bold;color:red;">NIE</span> 
	{/if}
</div>
<div>Dokument sprzedaży: 
	{if $gtState.gt_sell_doc_sent == 1} 
			<span style="font-weight: bold;color:green;">TAK</span> nr PA/FS.: <b>{$gtState.gt_sell_doc_ref}</b>
		{else} <span style="font-weight: bold;color:red;">NIE</span> 
	{/if}
</div>
<div>Wysłany dokument do klienta: 
	{if $gtState.customer_sell_doc_sent == 1} 
			<span style="font-weight: bold;color:green;">TAK</span> PDF
		{else} <span style="font-weight: bold;color:red;">NIE</span> 
	{/if}
</div>

</div>

<div style="float:right;width:48%;overflow:scroll;padding:5px;background-color: #fff;height:220px;">
	{foreach from=$logs item=log}
		{$log.log_date}: {$log.event_type} => {$log.result} : info=> {$log.result_desc}<br />
	{/foreach}
</div>
</fieldset>