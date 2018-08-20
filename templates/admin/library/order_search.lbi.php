<?php defined('IN_ECJIA') or exit('No permission resources.');?>
<div class="row-fluid ecjia-order-search display-none">
	<form class="form-horizontal search-form" action="{$search_url}" name="searchForm" method="post">
		<div class="search-item">
			<div class="item">
				<div class="control-group">
					<label class="control-label">订单号：</label>
					<div class="controls">
						<input class="w150" name="order_sn" type="text" size="40" placeholder="请输入订单编号关键字" />
					</div>
				</div>
			</div>
			<div class="item w500">
				<div class="control-group">
					<label class="control-label">下单时间：</label>
					<div class="controls">
						<input class="w150 date" name="start_time" type="text" value="" size="40" placeholder="请选择开始时间" /> &nbsp;至&nbsp;
						<input class="w150 date" name="end_time" type="text" value="" size="40" placeholder="请选择结束时间" />
					</div>
				</div>
			</div>
		</div>

		<div class="search-item">
			<div class="item">
				<div class="control-group">
					<label class="control-label">商家：</label>
					<div class="controls">
						<select name="merchants_name" />
						<option value="">请选择商家</option>
						</select>
					</div>
				</div>
			</div>
			<div class="item">
				<div class="control-group">
					<label class="control-label">订单状态：</label>
					<div class="controls">
						<select name="merchants_name" />
						<option value="">请选择订单状态</option>
						</select>
					</div>
				</div>
			</div>
			<div class="item">
				<div class="control-group">
					<label class="control-label">配送方式：</label>
					<div class="controls">
						<select name="merchants_name" />
						<option value="">请选择配送方式</option>
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
						<select name="merchants_name" />
						<option value="">请选择支付方式</option>
						</select>
					</div>
				</div>
			</div>
			<div class="item">
				<div class="control-group">
					<label class="control-label">下单渠道：</label>
					<div class="controls">
						<select name="merchants_name" />
						<option value="">请选择支付方式</option>
						</select>
					</div>
				</div>
			</div>
			<div class="item">
				<div class="control-group">
					<label class="control-label">商品名称：</label>
					<div class="controls">
						<input class="w150" name="goods_keywords" type="text" size="40" placeholder="请输入商品名称关键字" />
					</div>
				</div>
			</div>
		</div>

		<div class="search-item">
			<div class="item">
				<div class="control-group">
					<label class="control-label">购买人：</label>
					<div class="controls">
						<input class="w150" name="consignee_keywords" type="text" size="40" />
					</div>
				</div>
			</div>
			<div class="item">
				<div class="control-group">
					<label class="control-label">手机号：</label>
					<div class="controls">
						<input class="w150" name="mobile_keywords" type="text" size="40" />
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