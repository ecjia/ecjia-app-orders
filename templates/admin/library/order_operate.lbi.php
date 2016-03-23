<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<div class="modal hide fade" id="operate">
	<div class="modal-header">
		<button class="close" data-dismiss="modal">×</button>
		<h3>{$lang.order_operate}</h3>
	</div>
	<div class="modal-body">
		<div class="row-fluid">
			<div class="span12">
			<form class="form-horizontal" method="post" name="batchForm" action='{url path="orders/admin/operate_post"}'>
				<fieldset>
					<div class="control-group formSep control-group-small">
						<label class="control-label">{$lang.label_action_note}</label>
						<div class="controls">
							<textarea name="action_note" class="span10 lbi_action_note" cols="60" rows="3"></textarea>
						</div>
					</div>
					<div class="control-group formSep control-group-small ecjiaf-dn show_cancel_note">
						<label class="control-label">{$lang.label_cancel_note}</label>
						<div class="controls">
							<textarea name="cancel_note" class="span10" cols="60" rows="3" id="cancel_note">{$cancel_note}</textarea><br/>
							{$lang.notice_cancel_note}
						</div>
					</div>
					<div class="control-group formSep control-group-small ecjiaf-dn show_invoice_no">
						<label class="control-label">{$lang.label_invoice_no}</label>
						<div class="controls">
							<input name="invoice_no" type="text" class="span4" />
						</div>
					</div>
					<div class="control-group formSep control-group-small ecjiaf-dn show_refund">
						<label class="control-label">{$lang.label_handle_refund}</label>
						<div class="controls chk_radio">
				    		<label class="anonymous ecjiaf-dn"><input type="radio" name="refund" value="1" />{$lang.return_user_money}</label>
					      	<label><input type="radio" name="refund" value="2" />{$lang.create_user_account}</label>
					      	<label><input type="radio" name="refund" value="3" />{$lang.not_handle}</label>
						</div>
					</div>
					<div class="control-group formSep control-group-small ecjiaf-dn show_refund">
						<label class="control-label">{$lang.label_refund_note}</label>
						<div class="controls">
							<textarea name="refund_note" class="span10" cols="60" rows="3" id="refund_note">{$refund_note}</textarea>
						</div>
					</div>
					<div class="control-group t_c">
						<button class="btn btn-gebo batchsubmit" type="submit">{t}确定{/t}</button>
						<input type="hidden" name="order_id" value="{$order_id}" />
						<input class="batchtype" type='hidden' name='operation' />
						<input type="hidden" name="batch">
					</div>
				</fieldset>
			</form>
			</div>
		</div>
	</div>
</div>