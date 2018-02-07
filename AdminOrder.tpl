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
<div>Pobrano dokument sprzedaży: 
	{if $gtState.gt_sell_pdf_request == 1} 
			<span style="font-weight: bold;color:green;">TAK</span> <a href="{$module_path}selldoc_pdf.php?id_order={$id_order}"><img src="/img/t/AdminPDF.gif" /> {$gtState.doc_file_pdf}</a>
		{else} <span style="font-weight: bold;color:red;">NIE</span> 
	{/if}
</div>
<div>Wysłany dokument do klienta: 
	{if $gtState.email_sell_pdf_sent == 1} 
			<span style="font-weight: bold;color:green;">TAK</span>
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