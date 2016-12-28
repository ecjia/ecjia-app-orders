<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!-- {extends file="ecjia-merchant.dwt.php"} -->

<!-- {block name="home-content"} -->

<div class="page-header">
	<div class="pull-left">
		<h2><!-- {if $ur_here}{$ur_here}{/if} --></h2>
  	</div>
  		<div class="pull-right">
  		{if $action_link}
		<a href="{$action_link.href}" class="btn btn-primary data-pjax">
			<i class="fa fa-reply"></i> {$action_link.text}
		</a>
		{/if}
  	</div>
  	<div class="clearfix"></div>
</div>

<div class="row-fluid"">
	<div class="span12">
		<form action="{$form_action}" method="post" name="theForm">
			<div id="accordion2" class="foldable-list">
				<div class="accordion-group panel panel-default">
					<div class="panel-heading">
                            <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">
                                <h4 class="panel-title">
                                    <strong>{lang key='orders::order.base_info'}</strong>
                                </h4>
                            </a>
                     </div>
					<div class="accordion-body in collapse" id="collapseOne">
						<table class="table table-oddtd m_b0">
							<tbody class="first-td-no-leftbd">
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.return_time'}</strong></div></td>
								<td >{$back_order.formated_return_time}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_invoice_no'}</strong></div></td>
								<td colspan="3">{$back_order.invoice_no}</td>
							</tr>

							<tr>
								<td><div align="right"><strong>{lang key='orders::order.delivery_sn_number'}</strong></div></td>
								<td>{$back_order.delivery_sn}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_shipping_time'}</strong></div></td>
								<td>{$back_order.formated_update_time}</td>
							</tr>

							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_order_sn'}</strong></div></td>
								<td>
									<a href='{url path="orders/merchant/info" args="order_sn={$back_order.order_sn}"}'>{$back_order.order_sn}</a>
									{if $back_order.extension_code eq "group_buy"}
									<!-- <a href="group_buy.php?act=edit&id={$back_order.extension_id}">{$lang.group_buy}</a> -->
									{elseif $back_order.extension_code eq "exchange_goods"}
									<!-- <a href="exchange_goods.php?act=edit&id={$back_order.extension_id}">{$lang.exchange_goods}</a> -->
									{/if}
								</td>
								<td><div align="right"><strong>{lang key='orders::order.label_time'}</strong></div></td>
								<td>{$back_order.formated_add_time}</td>
							</tr>

							<tr>
								<td><div align="right"><strong>{lang key='orders::order.print_buy_name'}</strong></div></td>
								<td>{$back_order.user_name|default:$lang.anonymous}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_how_oos'}</strong></div></td>
								<td>{$back_order.how_oos}</td>
							</tr>

							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_shipping'}</strong></div></td>
								<td>
									{if $exist_real_goods}
									{if $back_order.shipping_id gt 0}
									{$back_order.shipping_name}
									{else}
									{$lang.require_field}
									{/if}
									{if $back_order.insure_fee gt 0}
									{$lang.label_insure_fee}{$back_order.formated_insure_fee}
									{/if}
									{/if}
								</td>
								<td><div align="right"><strong>{lang key='orders::order.label_shipping_fee'}</strong></div></td>
								<td>{$back_order.shipping_fee}</td>
							</tr>

							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_insure_yn'}</strong></div></td>
								<td>{if $insure_yn}{$lang.yes}{else}{$lang.no}{/if}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_insure_fee'}</strong></div></td>
								<td>{$back_order.insure_fee|default:0.00}</td>
							</tr>
							</tbody>
						</table>
					</div>
				</div>
				
				<div class="accordion-group panel panel-default">
					<div class="panel-heading">
                            <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo">
                                <h4 class="panel-title">
                                    <strong>{lang key='orders::order.buyer_info'}</strong>
                                </h4>
                            </a>
                    </div>
					<div class="accordion-body in collapse" id="collapseTwo">
						<table class="table table-oddtd m_b0">
							<tbody class="first-td-no-leftbd">
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_consignee'}</strong></div></td>
								<td>{$back_order.consignee|escape}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_email'}</strong></div></td>
								<td>{$back_order.email}</td>
							</tr>
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_address'}</strong></div></td>
								<td>[{$back_order.region}] {$back_order.address|escape}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_zipcode'}</strong></div></td>
								<td>{$back_order.zipcode|escape}</td>
							</tr>
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_tel'}</strong></div></td>
								<td>{$back_order.tel}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_mobile'}</strong></div></td>
								<td>{$back_order.mobile|escape}</td>
							</tr>
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_sign_building'}</strong></div></td>
								<td>{$back_order.sign_building|escape}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_best_time'}</strong></div></td>
								<td>{$back_order.best_time|escape}</td>
							</tr>
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_postscript'}</strong></div></td>
								<td colspan="3">{$back_order.postscript}</td>
							</tr>
							</tbody>
						</table>
					</div>
				</div>
				
				<div class="accordion-group panel panel-default">
					<div class="panel-heading">
                            <a data-toggle="collapse" data-parent="#accordion" href="#collapseThree">
                                <h4 class="panel-title">
                                    <strong>{lang key='orders::order.goods_info'}</strong>
                                </h4>
                            </a>
                    </div>
					<div class="accordion-body in collapse" id="collapseThree">
						<table class="table table-striped m_b0 order-table-list">
							<tbody>
							<tr class="table-list">
								<td>{lang key='orders::order.goods_name_brand'}</td>
								<td>{lang key='orders::order.goods_sn'}</td>
								<td>{lang key='orders::order.product_sn'}</td>
								<td>{lang key='orders::order.goods_attr'}</td>
								<td>{lang key='orders::order.label_send_number'}</td>
							</tr>
							<!-- {foreach from=$goods_list item=goods} -->
							<tr class="table-list">
								<td>
									<!-- {if $goods.goods_id gt 0 && $goods.extension_code neq 'package_buy'} -->
									<a href='{url path="goods/merchant/preview" args="id={$goods.goods_id}"}' target="_blank">{$goods.goods_name} {if $goods.brand_name}[ {$goods.brand_name} ]{/if}</a>
									<!-- {/if} -->
								</td>
								<td>{$goods.goods_sn}</td>
								<td>{$goods.product_sn}</td>
								<td>{$goods.goods_attr}</td>
								<td>{$goods.send_number}</td>
							</tr>
							<!-- {/foreach} -->
							</tbody>
						</table>
					</div>
				</div>
				<div class="accordion-group panel panel-default">
					<div class="panel-heading">
                            <a data-toggle="collapse" data-parent="#accordion" href="#collapseFour">
                                <h4 class="panel-title">
                                    <strong>{lang key='orders::order.action_info'}</strong>
                                </h4>
                            </a>
                    </div>
					<div class="accordion-body in collapse" id="collapseFour">
						<table class="table table-oddtd m_b0">
							<tbody class="first-td-no-leftbd">
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.action_user'}</strong></div></td>
							    <td>{$back_order.action_user}</td>
							</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<!-- {/block} -->