/**
 * Created by BWoods on 7/4/2017.
 */
/*function queryParams() {
    return {
        type: 'owner',
        sort: 'date_added',
        direction: 'desc',
        per_page: 100,
        page: 1
    };
}*/

function detailFormatter(index, row) {
    var html = [];

    (function ($) {
        $.each(row, function (key, value) {
            if (value != null)
                html.push('<p><b>' + key + ':</b> ' + value + '</p>');
        });
    })(jQuery);

    return html.join('');
}

var $table = (function ($) {
    $('#table');
});

function ajaxTransactionRequest(params) {
    console.log(params.data);
    console.log(ajax_object);

    jQuery(document).ready(function ($) {
        var $table = $('#table');

        $.post(ajax_object.ajax_url, {
                _ajax_nonce: ajax_object.nonce,
                action: "get_transaction_history",
                search: params.data.search,
                sort: params.data.sort,
                order: params.data.order,
                offset: params.data.offset,
                limit: params.data.limit,
            }
        ).success(function (data) {
            console.log(data);
            params.success({
                total: data.total,//data.length,
                rows: data.data
            });
        });
    });

}






