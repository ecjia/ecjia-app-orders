// JavaScript Document
;
(function (app, $) {
    app.order_stats = {
        init: function () {
            app.order_stats.searchForm();
        },
        
        searchForm: function () {
            $('.screen-btn').off('click').on('click', function (e) {
                e.preventDefault();
                var year = $("select[name='year']").val(); //开始时间
                var month = $("select[name='month']").val(); //结束时间
                var url = $("form[name='searchForm']").attr('action'); //请求链接
                
                if (year == 0 || year == undefined) {
                	ecjia.admin.showmessage({'state': 'error', 'message': '请选择年份'});
                	return false;
                }
                url += '&year=' + year;

                if (month != undefined && month != 0) {
                	url += '&month=' + month;
                }
                ecjia.pjax(url);
            });
            
            $('.search-btn').off('click').on('click', function (e) {
                e.preventDefault();
                var keywords = $("input[name='keywords']").val();
                var url = $("form[name='searchForm']").attr('action'); //请求链接
                
                if (keywords != '' && keywords != undefined) {
                	url += '&keywords=' + keywords;
                }
                ecjia.pjax(url);
            });
        },
    };
    
})(ecjia.admin, jQuery);
 
// end