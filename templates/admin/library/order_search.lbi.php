<div class="row-fluid ecjia-order-search {if !$smarty.get.show_search}display-none{/if}">
	<form class="form-horizontal search-form" action="{$search_url}" name="advancedSearchForm" method="post">
		<div class="search-item">
			<div class="item">
				<div class="control-group">
					<label class="control-label">订单号：</label>
					<div class="controls">
						<input class="w165" name="order_sn" type="text" value="{$filter.order_sn}" size="40" placeholder="请输入订单编号关键字" />
					</div>
				</div>
			</div>
			<div class="item w520">
				<div class="control-group">
					<label class="control-label">下单时间：</label>
					<div class="controls">
						<input class="w165 date" name="start_time" type="text" value="{$filter.start_time}" size="40" placeholder="请选择开始时间" /> &nbsp;至&nbsp;
						<input class="w165 date" name="end_time" type="text" value="{$filter.end_time}" size="40" placeholder="请选择结束时间" />
					</div>
				</div>
			</div>
		</div>

		<div class="search-item">
			<div class="item">
				<div class="control-group">
					<label class="control-label">商家：</label>
					<div class="controls">
						<select name="merchants_name" class="w180">
							<option value="">请选择商家</option>
							{foreach from=$merchant_list item=val}
							<option value="{$val.store_id}">{$val.merchants_name}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
			<div class="item">
				<div class="control-group">
					<label class="control-label">订单状态：</label>
					<div class="controls">
						<select name="order_status" class="w180">
							<option value="">请选择订单状态</option>
							<!-- {html_options options=$status_list selected=$filter.composite_status} -->
						</select>
					</div>
				</div>
			</div>
			<div class="item">
				<div class="control-group">
					<label class="control-label">配送方式：</label>
					<div class="controls">
						<select name="shipping_id" class="w180">
							<option value="">请选择配送方式</option>
							{foreach from=$shipping_list item=val}
							<option value="{$val.shipping_id}">{$val.shipping_name}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="search-item">
			<div class="item">
				<div class="control-group">
					<label class="control-label">支付方式：</label>
					<div class="controls">
						<select name="pay_id" class="w180">
							<option value="">请选择支付方式</option>
							{foreach from=$pay_list item=val}
							<option value="{$val.pay_id}">{$val.pay_name}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
			<div class="item">
				<div class="control-group">
					<label class="control-label">下单渠道：</label>
					<div class="controls">
						<select name="referer" class="w180">
							<option value="">请选择下单渠道</option>
							<option value="iphone">iPhone端</option>
							<option value="android">Andriod端</option>
							<option value="mobile">H5端</option>
							<option value="ecjia-cashdesk">收银台</option>
							<option value="weapp">小程序</option>
						</select>
					</div>
				</div>
			</div>
			<div class="item">
				<div class="control-group">
					<label class="control-label">商品名称：</label>
					<div class="controls">
						<input class="w165" name="goods_keywords" type="text" size="40" placeholder="请输入商品名称关键字" />
					</div>
				</div>
			</div>
		</div>

		<div class="search-item">
			<div class="item">
				<div class="control-group">
					<label class="control-label">购买人：</label>
					<div class="controls">
						<input class="w165" name="consignee_keywords" type="text" size="40" />
					</div>
				</div>
			</div>
			<div class="item">
				<div class="control-group">
					<label class="control-label">手机号：</label>
					<div class="controls">
						<input class="w165" name="mobile_keywords" type="text" size="40" />
					</div>
				</div>
			</div>
		</div>

		<div class="search-item">
			<div class="item">
				<div class="control-group">
					<label class="control-label"></label>
					<div class="controls">
						<input class="btn btn-gebo" type="submit" value="查询" />
						<input class="btn" type="reset" value="重置" />
						<input class="btn hide" type="button" value="导出报表" />
					</div>
				</div>
			</div>
		</div>
	</form>
</div>