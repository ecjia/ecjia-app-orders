<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<!--{extends file="ecjia-merchant.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
ecjia.merchant.order.info();
</script>
<!-- {/block} -->

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
<!-- #BeginLibraryItem "/library/order_operate.lbi" --><!-- #EndLibraryItem -->
<!-- #BeginLibraryItem "/library/order_refund.lbi" --><!-- #EndLibraryItem -->

	<div id="actionmodal" class="modal fade">
        <div class="modal-dialog" style="margin-top: 200px;">
            <div class="modal-content">
                <div class="modal-header">
                    <button data-dismiss="modal" class="close" type="button">×</button>
                    <h4 class="modal-title">订单操作：退款/退货</h4>
                </div>
                
                <div class="modal-body">
                	  <div class="success-msg"></div>
		              <div class="error-msg"></div>
                      <form class="form-horizontal" method="post" name="actionForm" id="actionForm" action='{url path="orders/merchant/mer_action_return"}'>
                      
					   <div class="form-group">
							<label class="control-label col-lg-3">退款方式：</label>
							<div class="controls col-lg-8">
								<select class="form-control" id="refund_type_select" name="refund_type_select" class="w200">
					                <option value="">请选择…</option>
					                <option value="refund">仅退款</option>
					                <option value="return">退货退款</option>
					            </select>
							</div>
						</div>
						
						<div id="refund_type_select_return">
							<div class="form-group">
					    		<label class="control-label col-lg-3">返还方式：</label>
								<div class="col-lg-8 chk_radio return_shipping_range">
									<input type="checkbox" name="return_shipping_range" id="home" value="home" > 
									<label for="home"><strong>上门取件</strong></label><small>（由商家联系配送员上门取件）</small>
					    			<br/>
					    			 
					    			<input name="return_shipping_range" id="express" value="express" type="checkbox"> 
									<label for="express"><strong>自选快递</strong></label><small>（由用户自选第三方快递公司配送）</small>
									<div class="return_shipping_content">
										<p>收件人：{$return_shipping_content.staff_name} &nbsp;&nbsp;&nbsp;手机：{$return_shipping_content.staff_mobile}</p>
										<p>地址：{$return_shipping_content.address}</p>
									</div>
									<br/>
								  
								    <input name="return_shipping_range" id="shop" value="shop" type="checkbox"> 
									<label for="shop"><strong>到店退货</strong></label><small>（由用户到门店线下退货）</small>
									<div class="return_shipping_content">
										<p>店铺名称：{$return_shipping_content.store_name} &nbsp;&nbsp;&nbsp;电话：{$return_shipping_content.shop_kf_mobile}</p>
										<p>地址：{$return_shipping_content.address}</p>
									</div>
								</div>
							</div>
						</div>	
						
						<div class="form-group">
							<label class="control-label col-lg-3">退款原因：</label>
							<div class="controls col-lg-8">
								<select class="form-control" id="refund_reason_select" name="refund_reason_select" class="w200">
									<option value="">请选择…</option>
					                <option value="91">暂不想购买了</option>
									<option value="92">信息填写有误，重新购买</option>
									<option value="93">外表损伤（包装，商品等）</option>
									<option value="94">商品质量问题</option>
									<option value="95">发错货</option>
									<option value="96">未在时效内送达</option>
									<option value="97">服务态度问题</option>					
					            </select>
							</div>
						</div>
						
						<div class="form-group">
							<label class="control-label col-lg-3">退款说明：</label>
							<div class="controls col-lg-8">
								<textarea name="refund_content" cols="60" rows="3" class="form-control" id="refund_content"></textarea>
							</div>
						</div>
						
						<div class="form-group">
							<label class="control-label col-lg-3">操作备注：</label>
							<div class="controls col-lg-8">
								<textarea name="merchant_action_note" cols="60" rows="3" class="form-control" id="merchant_action_note"></textarea>
							</div>
						</div>
						
						
                        <div class="control-group t_c">
							<button class="btn btn-info" id="note_btn" type="submit">{t}确定{/t}</button>
							<input type="hidden" name="order_id" value="{$order_id}" />
						</div>
                    </form>
                </div>
            </div>
        </div>
   	</div>
   	
<div class="modal fade" id="consigneeinfo" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3>{t}购货人信息{/t}</h3>
            </div>
            <div class="modal-body">
                <div class="row-fluid">
					<div class="span12">
						<table class="table table-bordered">
							<tr><td colspan="2"><strong>购货人信息</strong></td></tr>
							<tr><td class="w200">{lang key='orders::order.email'}</td><td>{$user.email}</td></tr>
							<tr><td>{lang key='orders::order.user_money'}</td><td>{$user.user_money}</td></tr>
							<tr><td>{lang key='orders::order.pay_points'}</td><td>{$user.pay_points}</td></tr>
							<tr><td>{lang key='orders::order.rank_points'}</td><td>{$user.rank_points}</td></tr>
							<tr><td>{lang key='orders::order.rank_name'}</td><td>{$user.rank_name}</td></tr>
							<tr><td>{lang key='orders::order.bonus_count'}</td><td>{$user.bonus_count}</td></tr>
						</table>
						<!-- {foreach from=$address_list item=address} -->
						<table class="table table-bordered">
							<tr><td colspan="2"><strong>{lang key='orders::order.label_consignee'}{$order.consignee|default:$order.user_name}</strong></td></tr>
							<tr><td class="w200">{lang key='orders::order.email'}</td><td>{$address.email}</td></tr>
							<tr><td>{lang key='orders::order.address'}</td><td>{$address.address}{$address.address_info}</td></tr>
							<tr><td>{lang key='orders::order.zipcode'}</td><td>{$address.zipcode}</td></tr>
							<tr><td>{lang key='orders::order.tel'}</td><td>{$address.tel}</td></tr>
							<tr><td>{lang key='orders::order.mobile'}</td><td>{$address.mobile}</td></tr>
						</table>
						<!-- {/foreach} -->
					</div>
				</div>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-body">
	<div class="order-status-base m_b20">
		<ul class="">
			<li class="step-first">
				<div class="{if $time_key lt '2'}step-cur{else}step-done{/if}">
					<div class="step-no">{if $time_key lt '2'}1{/if}</div>
					<div class="m_t5">{lang key='orders::order.submit_order'}</div>
					<div class="m_t5 ecjiafc-blue">{$order.formated_add_time}</div>
				</div>
			</li>
			<li>
				<div class="{if $time_key eq '2'}step-cur{elseif $time_key gt '2' && $pay_key}step-done{else}step-pay{/if}">
					<div class="step-no">{if $time_key eq '2' || !$pay_key}2{/if}</div>
					<div class="m_t5">{lang key='orders::order.pay_for_order'}</div>
					<div class="m_t5 ecjiafc-blue">{$order.pay_time}</div>
				</div>
			</li>
			<li>
				<div class="{if $time_key eq '3'}step-cur{elseif $time_key gt '3'}step-done{/if}">
					<div class="step-no">{if $time_key lt '4'}3{/if}</div>
					<div class="m_t5">{lang key='orders::order.merchant_shipping'}</div>
				</div>
			</li>
			<li class="step-last">
				<div class="{if $time_key eq '4'}step-cur{elseif $time_key gt '4'}step-done{/if}">
					<div class="step-no">{if $time_key lt '5'}4{/if}</div>
					<div class="m_t5">{lang key='orders::order.confirm_receipt'}</div>
				</div>
			</li>
		</ul>
	</div>
</div>

<div class="row">
	<div class="col-lg-12 panel-heading form-inline">
		<div class="form-group"><h3>{lang key='orders::order.label_order_sn'}{$order.order_sn}</h3></div>

		<div class="form-group order-info-search">
			<input type="text" name="keywords" class="form-control" placeholder="请输入订单号或者订单id" />
			<button class="btn btn-primary queryinfo" type="button" data-url='{url path="orders/merchant/query_info"}'>{t}搜索{/t}</button>

		</div>
		<div class="form-group pull-right">
			{if $next_id}
			<a class="data-pjax ecjiaf-tdn" href='{url path="orders/merchant/info" args="order_id={$next_id}"}'>
			{/if}
				<button class="btn btn-primary" type="button" {if !$next_id}disabled="disabled"{/if}>{lang key='orders::order.prev'}</button>
			{if $next_id}
			</a>
			{/if}
			{if $prev_id}
			<a class="data-pjax ecjiaf-tdn" href='{url path="orders/merchant/info" args="order_id={$prev_id}"}' >
			{/if}
				<button class="btn btn-primary" type="button" {if !$prev_id}disabled="disabled"{/if}>{lang key='orders::order.next'}</button>
			{if $prev_id}
			</a>
			{/if}
			<div class="btn-group form-group">
        		<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">打印 <span class="caret"></span></button>
        		<ul class="dropdown-menu pull-right">
        			<li><a class="nopjax" href='{url path="orders/merchant/info" args="order_id={$order.order_id}&print=1"}' target="__blank">订单打印</a></li>
        			{if $has_payed eq 1}
        			<li><a class="toggle_view" href='{url path="orders/mh_print/init" args="order_id={$order.order_id}"}'>小票打印</a></li>
            		{/if}
            	</ul>
        	</div>
		</div>
	</div>
</div>
<div class="row-fluid">
	<div class="span12">
		<form action="{$form_action}" method="post" name="orderpostForm" id="listForm" data-url='{url path="orders/merchant/operate_post" args="order_id={$order.order_id}"}'  data-pjax-url='{url path="orders/merchant/info" args="order_id={$order.order_id}"}' data-list-url='{url path="orders/merchant/init"}' data-remove-url="{$remove_action}">
			<div id="accordion2" class="panel panel-default">
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
								<td><div align="right"><strong>{lang key='orders::order.label_order_sn'}</strong></div></td>
								<!-- TODO 团购链接赞不知，以后修改测试 -->
								<td>
									{$order.order_sn}
									{if $order.extension_code eq "group_buy"}
<!-- 										<a href="group_buy.php?act=edit&id={$order.extension_id}">{lang key='orders::order.group_buy'}</a> -->
									{elseif $order.extension_code eq "exchange_goods"}
<!-- 										<a href="exchange_goods.php?act=edit&id={$order.extension_id}">{lang key='orders::order.exchange_goods'}</a> -->
									{/if}
								</td>
								<td><div align="right"><strong>{lang key='orders::order.label_order_status'}</strong></div></td>
								<td>{$order.status}</td>
							</tr>
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_user_name'}</strong></div></td>
								<td>
									{$order.user_name|default:{lang key='orders::order.anonymous'}}
									{if $order.user_id gt 0}
									[ <a class="userInfo cursor_pointer" data-toggle="modal" data-target="#consigneeinfo" title="{lang key='orders::order.display_buyer'}">{lang key='orders::order.display_buyer'}</a> ]
									{/if}
								</td>
								<td><div align="right"><strong>{lang key='orders::order.label_order_time'}</strong></div></td>
								<td>{$order.formated_add_time}</td>
							</tr>
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_payment'}</strong></div></td>
								<td>
									{$order.pay_name}
									{if $order_finished neq 1 && $order.shipping_status neq 1 && !$invalid_order}
									<a class="data-pjax" href='{url path="orders/merchant/edit" args="order_id={$order.order_id}&step=shipping"}'>{lang key='system::system.edit'}</a>
									{/if}
									({lang key='orders::order.label_action_note'}<span>{if $order.pay_note}{$order.pay_note}{else}暂无{/if}</span>)
								</td>
								<td><div align="right"><strong>{lang key='orders::order.label_pay_time'}</strong></div></td>
								<td>{$order.pay_time}</td>
							</tr>
							
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_shipping'}</strong></div></td>
								<td>
									{if $exist_real_goods}
										<span>{if $order.shipping_name}{$order.shipping_name}{/if}</span>
										{if $order.shipping_id gt 0}
											{if $order_finished neq 1 && !$invalid_order}
											<a class="data-pjax" href='{url path="orders/merchant/edit" args="order_id={$order.order_id}&step=shipping"}'>{lang key='system::system.edit'}</a>
											{/if}
											
											{if $shipping_code == 'ship_cac'}
												(提货码：{if $meta_value neq ''}{$meta_value}{else}暂无{/if})
											{else}
												<input type="button" class="btn btn-primary" onclick="window.open('{url path="orders/merchant/info" args="order_id={$order.order_id}&shipping_print=1"}')" value="{lang key='orders::order.print_shipping'}">
											{/if}
										{/if}
											
										
										{if $order.insure_fee gt 0}
											{lang key='orders::order.label_insure_fee'}{$order.formated_insure_fee}
										{/if}
									{/if}
								</td>
								<td><div align="right"><strong>{lang key='orders::order.label_shipping_time'}</strong></div></td>
								<td>{$order.shipping_time}</td>
							</tr>
							
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_invoice_no'}</strong></div></td>
								<td>
									{if $order.shipping_id gt 0 and $order.shipping_status gt 0}
										<span>{if $order.invoice_no}{$order.invoice_no}{else}暂无{/if}</span>&nbsp;
										{if $order_finished neq 1}
											<a href='{url path="orders/merchant/edit" args="order_id={$order.order_id}&step=shipping"}' class="special data-pjax">{lang key='system::system.edit'}</a>
										{/if}
									{/if}
								</td>
								<td><div align="right"><strong>{lang key='orders::order.from_order'}</strong></div></td>
								<td>{if $order.referer eq 'ecjia-cashdesk'}收银台{else}{$order.referer}{/if}</td>
							</tr>
							
							<!-- {if $order.express_user} -->
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_express_user'}</strong></div></td>
								<td>{$order.express_user}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_express_user_mobile'}</strong></div></td>
								<td>{$order.express_mobile}</td>
							</tr>
							<!-- {/if}  -->
							
							<tr>
								<td><div align="right"><strong>期望送达时间：</strong></div></td>
								<td colspan="3">{$order.expect_shipping_time}</td>
							</tr>
							
						</tbody>
					</table>
				</div>
			</div>
			<div class="accordion-group panel panel-default">
				<div class="panel-heading accordion-group-heading-relative">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo">
                        <h4 class="panel-title">
                            <strong>{t}发票信息{/t}</strong>
                        </h4>
                    </a>
                    {if $order_finished neq 1 && $order.shipping_status neq 1 && !$invalid_order}
						<a class="data-pjax accordion-group-heading-absolute" href='{url path="orders/merchant/edit" args="order_id={$order.order_id}&step=other"}'>{lang key='system::system.edit'}</a>
					{/if}
                </div>
                <div class="accordion-body in collapse " id="collapseTwo">
                	<table class="table table-oddtd m_b0">
						<tbody class="first-td-no-leftbd">
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_inv_type'}</strong></div></td>
								<td>{$order.inv_type}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_inv_tax_no'}</strong></div></td>
								<td>{$inv_tax_no}</td>
							</tr>
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_inv_payee'}</strong></div></td>
								<td>{$inv_payee}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_inv_content'}</strong></div></td>
								<td>{$order.inv_content}</td>
							</tr>
						</tbody>
					</table>
                </div>
			</div>
			<div class="accordion-group panel panel-default">
				<div class="panel-heading accordion-group-heading-relative">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseThree">
                        <h4 class="panel-title">
                            <strong>{lang key='orders::order.other_info'}</strong>
                        </h4>
                    </a>
                    {if $order_finished neq 1 && $order.shipping_status neq 1 && !$invalid_order}
						<a class="data-pjax accordion-group-heading-absolute" href='{url path="orders/merchant/edit" args="order_id={$order.order_id}&step=other"}'>{lang key='system::system.edit'}</a>
					{/if}
                </div>
                <div class="accordion-body in collapse " id="collapseThree">
                	<table class="table table-oddtd m_b0">
						<tbody class="first-td-no-leftbd">
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_postscript'}</strong></div></td>
								<td colspan="3">{$order.postscript}</td>
							</tr>
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_how_oos'}</strong></div></td>
								<td colspan="3">{$order.how_oos}</td>
							</tr>
<!-- 							<tr> -->
<!-- 								<td><div align="right"><strong>{lang key='orders::order.label_pack'}</strong></div></td> -->
<!-- 								<td>{$order.pack_name}</td> -->
<!-- 								<td><div align="right"><strong>{lang key='orders::order.label_card'}</strong></div></td> -->
<!-- 								<td>{$order.card_name}</td> -->
<!-- 							</tr> -->
<!-- 							<tr> -->
<!-- 								<td><div align="right"><strong>{lang key='orders::order.label_card_message'}</strong></div></td> -->
<!-- 								<td colspan="3">{$order.card_message}</td> -->
<!-- 							</tr> -->
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_to_buyer'}</strong></div></td>
								<td colspan="3">{$order.to_buyer}</td>
							</tr>
						</tbody>
					</table>
                </div>
			</div>
			<div class="accordion-group panel panel-default">
				<div class="panel-heading accordion-group-heading-relative">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseFour">
                        <h4 class="panel-title">
                            <strong>{lang key='orders::order.consignee_info'}</strong>
                        </h4>
                    </a>
                    {if $order_finished neq 1 && $order.shipping_status neq 1 && !$invalid_order}
						<a class="data-pjax accordion-group-heading-absolute" href='{url path="orders/merchant/edit" args="order_id={$order.order_id}&step=consignee"}'>{lang key='system::system.edit'}</a>
					{/if}
                </div>
                <div class="accordion-body in collapse " id="collapseFour">
                	<table class="table table-oddtd m_b0">
						<tbody class="first-td-no-leftbd">
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_consignee'}</strong></div></td>
								<td>{$order.consignee}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_address'}</strong></div></td>
								<td>[{$order.region}]{$order.address}</td>
							</tr>
<!-- 							<tr> -->
<!-- 								<td><div align="right"><strong>{lang key='orders::order.label_email'}</strong></div></td> -->
<!-- 								<td>{$order.email}</td> -->
<!-- 								<td><div align="right"><strong>{lang key='orders::order.label_zipcode'}</strong></div></td> -->
<!-- 								<td>{$order.zipcode}</td> -->
<!-- 							</tr> -->
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_tel'}</strong></div></td>
								<td>{$order.tel}</td>
								<td><div align="right"><strong>{lang key='orders::order.label_mobile'}</strong></div></td>
								<td>{$order.mobile}</td>
							</tr>
<!-- 							<tr> -->
<!-- 								<td><div align="right"><strong>{lang key='orders::order.label_sign_building'}</strong></div></td> -->
<!-- 								<td>{$order.sign_building|escape}</td> -->
<!-- 								<td><div align="right"><strong>{lang key='orders::order.label_expect_shipping_time'}</strong></div></td> -->
<!-- 								<td>{$order.expect_shipping_time}</td> -->
<!-- 							</tr> -->
						</tbody>
					</table>
                </div>
			</div>
			<div class="accordion-group panel panel-default">
				<div class="panel-heading accordion-group-heading-relative">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseFive">
                        <h4 class="panel-title">
                            <strong>{lang key='orders::order.goods_info'}</strong>
                        </h4>
                    </a>
                    {if $order_finished neq 1 && $order.shipping_status neq 1}
<!-- 						<a class="data-pjax accordion-group-heading-absolute" href='{url path="orders/merchant/edit" args="order_id={$order.order_id}&step=goods"}'>{lang key='system::system.edit'}</a> -->
					{/if}
                </div>
                <div class="accordion-body in collapse " id="collapseFive">
                	<table class="table table-striped table_vam m_b0 order-table-list">
						<thead>
							<tr class="table-list">
								<th class="w130">{t}商品缩略图{/t}</th>
								<th>{lang key='orders::order.goods_name_brand'}</th>
								<th class="w80">{lang key='orders::order.goods_sn'}</th>
								<th class="w70">{lang key='orders::order.product_sn'}</th>
								<th class="w100">{lang key='orders::order.goods_price'}</th>
								<th class="w50">{lang key='orders::order.goods_number'}</th>
								<th class="w100">{lang key='orders::order.goods_attr'}</th>
								<th class="w50">{lang key='orders::order.storage'}</th>
								<th class="w100">{lang key='orders::order.subtotal'}</th>
							</tr>
						</thead>
						<tbody>
							<!-- {foreach from=$goods_list item=goods} -->
							<tr class="table-list">
								<td><img src="{$goods.goods_img}" width='50'/></td>
								<td>
									{if $goods.goods_id gt 0 and $goods.extension_code neq 'package_buy'}
									<a href='{url path="goods/merchant/preview" args="id={$goods.goods_id}"}' target="_blank">{$goods.goods_name} {if $goods.brand_name}[ {$goods.brand_name} ]{/if}{if $goods.is_gift}{if $goods.goods_price gt 0}{lang key='orders::order.remark_favourable'}{else}{lang key='orders::order.remark_gift'}{/if}{/if}{if $goods.parent_id gt 0}{lang key='orders::order.remark_fittings'}{/if}</a>
									{elseif $goods.goods_id gt 0 and $goods.extension_code eq 'package_buy'}
									<!-- <a href="javascript:void(0)" onclick="setSuitShow({$goods.goods_id})">{$goods.goods_name}<span style="color:#FF0000;">{lang key='orders::order.remark_package'}</span></a> -->
									<!-- <div style="display:none">  -->
									<!-- {foreach from=$goods.package_goods_list item=package_goods_list} -->
									<!-- <a href='{url path="goods/merchant/preview" args="id={$package_goods_list.goods_id}"}' target="_blank">{$package_goods_list.goods_name}</a><br /> -->
									<!-- {/foreach} -->
									<!-- </div> -->
									{/if}
								</td>
								<td>{$goods.goods_sn}</td>
								<td>{$goods.product_sn}</td>
								<td><div >{$goods.formated_goods_price}</div></td>
								<td><div >{$goods.goods_number}
								</div></td>
								<td>{$goods.goods_attr|nl2br}</td>
								<td><div >{$goods.storage}</div></td>
								<td><div >{$goods.formated_subtotal}</div></td>
							</tr>
							<!-- {foreachelse} -->
							<tr>
								<td class="no-records" colspan="9">{t}该订单暂无商品{/t}</td>
							</tr>
							<!-- {/foreach} -->
							<tr>
								<td colspan="4">{if $order.total_weight}<div align="right"><strong>{lang key='orders::order.label_total_weight'}
								</strong></div>{/if}</td>
								<td colspan="1">{if $order.total_weight}<div align="right">{$order.total_weight}
								</div>{/if}</td>
								<td colspan="3"><div align="right"><strong>{lang key='orders::order.label_total'}</strong></div></td>
								<td><div align="right">{$order.formated_goods_amount}</div></td>
							</tr>
						</tbody>
					</table>
                </div>
			</div>
			<div class="accordion-group panel panel-default">
				<div class="panel-heading accordion-group-heading-relative">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseSix">
                        <h4 class="panel-title">
                            <strong>{lang key='orders::order.fee_info'}</strong>
                        </h4>
                    </a>
                    {if $order_finished neq 1 && $order.shipping_status neq 1 && !$invalid_order}
						<a class="data-pjax accordion-group-heading-absolute" href='{url path="orders/merchant/edit" args="order_id={$order.order_id}&step=money"}'>{lang key='system::system.edit'}</a>
					{/if}
                </div>
                <div class="accordion-body in collapse " id="collapseSix">
                	<table class="table m_b0">
						<tr>
							<td>
								<div align="right">
									{lang key='orders::order.label_goods_amount'}<strong>{$order.formated_goods_amount}</strong>
									- {lang key='orders::order.label_discount'}<strong>{$order.formated_discount}</strong>
									+ {lang key='orders::order.label_tax'}<strong>{$order.formated_tax}</strong>
									+ {lang key='orders::order.label_shipping_fee'}<strong>{$order.formated_shipping_fee}</strong>
									+ {lang key='orders::order.label_insure_fee'}<strong>{$order.formated_insure_fee}</strong>
									+ {lang key='orders::order.label_pay_fee'}<strong>{$order.formated_pay_fee}</strong>
									+ {lang key='orders::order.label_pack_fee'}<strong>{$order.formated_pack_fee}</strong>
									+ {lang key='orders::order.label_card_fee'}<strong>{$order.formated_card_fee}</strong>
								</div>
							</td>
						</tr>
						<tr>
							<td><div align="right"> = {lang key='orders::order.label_order_amount'}<strong>{$order.formated_total_fee}</strong></div></td>
						</tr>
						<tr>
							<td>
								<div align="right">
									- {lang key='orders::order.label_money_paid'}<strong>{$order.formated_money_paid}</strong>
									- {lang key='orders::order.label_surplus'} <strong>{$order.formated_surplus}</strong>
									- {lang key='orders::order.label_integral'} <strong>{$order.formated_integral_money}</strong>
									- {lang key='orders::order.label_bonus'} <strong>{$order.formated_bonus}</strong>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<div align="right">
									= {if $order.order_amount >= 0}
									{lang key='orders::order.label_money_dues'}<strong>{$order.formated_order_amount}</strong>
									{else}
									{lang key='orders::order.label_money_refund'}<strong>{$order.formated_money_refund}</strong>
									<input class="refund_click btn btn-info" type="button" data-href="{$refund_url}" value="{lang key='orders::order.refund'}">
									{/if}
									{if $order.extension_code eq "group_buy"}<br />{lang key='orders::order.notice_gb_order_amount'}{/if}
								</div>
							</td>
						</tr>
					</table>
                </div>
			</div>
			<div class="accordion-group panel panel-default">
				<div class="panel-heading">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseSeven">
                        <h4 class="panel-title">
                            <strong>{t}操作记录{/t}</strong>
                        </h4>
                    </a>
                </div>
                <div class="accordion-body in collapse" id="collapseSeven">
                	<table class="table table-striped m_b0">
						<thead>
							<tr>
								<th class="w150"><strong>操作者</strong></th>
								<th class="w180"><strong>{lang key='orders::order.action_time'}</strong></th>
								<th class="w130"><strong>{lang key='orders::order.order_status'}</strong></th>
								<th class="w130"><strong>{lang key='orders::order.pay_status'}</strong></th>
								<th class="w130"><strong>{lang key='orders::order.shipping_status'}</strong></th>
								<th class="ecjiafc-pre t_c"><strong>{lang key='orders::order.action_note'}</strong></th>
							</tr>
						</thead>
						<tbody>
							<!-- {foreach from=$action_list item=action} -->
							<tr>
								<td>{$action.action_user}</td>
								<td>{$action.action_time}</td>
								<td>{$action.order_status}</td>
								<td>{$action.pay_status}</td>
								<td>{$action.shipping_status}</td>
								<td class="t_c">{$action.action_note|nl2br}</td>
							</tr>
							<!-- {foreachelse} -->
							<tr>
								<td class="no-records w200" colspan="6">{t}该订单暂无操作记录{/t}</td>
							</tr>
							<!-- {/foreach} -->
						</tbody>
					</table>
                </div>
			</div>
			{if !$invalid_order}
			<div class="accordion-group panel panel-default">
				<div class="panel-heading">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseEight">
                        <h4 class="panel-title">
                            <strong>{t}订单操作{/t}</strong>
                        </h4>
                    </a>
                </div>
                <div class="accordion-body in collapse " id="collapseEight">
                	<table class="table table-oddtd m_b0">
						<tbody class="first-td-no-leftbd">
							<tr>
								<td width="15%"><div align="right"><span class="input-must">*</span> <strong>{lang key='orders::order.label_action_note'}</strong></div></td>
								<td colspan="3"><textarea name="action_note" class="span12 action_note form-control" cols="60" rows="3"></textarea></td>
							</tr>
							<tr>
								<td><div align="right"><strong>{lang key='orders::order.label_operable_act'}</strong></div></td>
								<td colspan="3">
									<input type='hidden' class="operate_note" data-url='{url path="orders/merchant/operate_note"}'>

									{if $operable_list.confirm}
									<button class="btn operatesubmit btn-info" type="submit" name="confirm">{lang key='orders::order.op_confirm'}</button>
									{/if}

									{if $operable_list.pay}
									<button class="btn operatesubmit btn-info" type="submit" name="pay">{lang key='orders::order.op_confirm_pay'}</button>
									{/if}

									{if $operable_list.unpay}
									<button class="btn operatesubmit btn-info" type="submit" name="unpay">{lang key='orders::order.op_unpay'}</button>
									{/if}

									{if $operable_list.prepare}
									<button class="btn operatesubmit btn-info" type="submit" name="prepare">{lang key='orders::order.op_prepare'}</button>
									{/if}

									{if $operable_list.split}
									<button class="btn operatesubmit btn-info" type="submit" name="ship">{lang key='orders::order.op_split'}</button>
									{/if}

									{if $operable_list.unship}
									<button class="btn operatesubmit btn-info" type="submit" name="unship">{lang key='orders::order.op_unship'}</button>
									{/if}

<!-- 									{if $operable_list.receive} -->
<!-- 									<button class="btn operatesubmit btn-info" type="submit" name="receive">{lang key='orders::order.op_receive'}</button> -->
<!-- 									{/if}  -->

									{if $operable_list.cancel}
									<button class="btn operatesubmit btn-info" type="submit" name="cancel">{lang key='orders::order.op_cancel'}</button>
									{/if}

									{if $operable_list.invalid}
									<button class="btn operatesubmit btn-info" type="submit" name="invalid">{lang key='orders::order.op_invalid'}</button>
									{/if}

									{if $operable_list.return}
									<button class="btn operatesubmit btn-info" type="submit" name="return">{lang key='orders::order.op_return'}</button>
									{/if}
									
									<a style="cursor: pointer;" class="btn btn-info" href="#actionmodal" data-toggle="modal" id="modal">退款/退货</a>

									{if $operable_list.to_delivery}
									<button class="btn operatesubmit btn-info" type="submit" name="to_delivery">{lang key='orders::order.op_to_delivery'}</button>
									<input name="order_sn" type="hidden" value="{$order.order_sn}" />
									{/if}

									<button class="btn operatesubmit btn-info" type="submit" name="after_service">{lang key='orders::order.op_after_service'}</button>
									{if $operable_list.remove}
									<button class="btn operatesubmit btn-info" type="submit" name="remove">{lang key='orders::order.remove'}</button>
									{/if}

									{if $order.extension_code eq "group_buy"}{lang key='orders::order.notice_gb_ship'}{/if}
									<input name="order_id" class="order_id" type="hidden" value="{$order.order_id}">
								</td>
							</tr>
						</tbody>
					</table>
                </div>
			</div>
			{/if}
		</form>
	</div>
</div>
<!-- {/block} -->