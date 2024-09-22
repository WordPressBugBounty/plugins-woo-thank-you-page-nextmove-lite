jQuery(document).ready(function ($) {
    var noticeWrap = $('#xl_notice_type_3');
    var pluginShortSlug = noticeWrap.attr("data-plugin");
    var pluginSlug = noticeWrap.attr("data-plugin-slug");
    $('body').on('click', '.xl-notice-dismiss', function (e) {
        e.preventDefault();
        var $this = $(this);
        var nonce = xlwcty_notice_vars.nonce;

        noticeWrap = $this.parents('#xl_notice_type_3');
        pluginShortSlug = noticeWrap.attr("data-plugin");

        var xlDisplayedMode = $this.attr("data-mode");
        if ('dismiss' == xlDisplayedMode) {
            xlDisplayedCount = '100';
        } else if ('later' == xlDisplayedMode) {
            xlDisplayedCount = '+1';
        }
        wp.ajax.send('nextmove_upsells_dismiss', {
            data: {
                plugin: pluginShortSlug,
                notice_displayed_count: xlDisplayedCount,
                xli_nonce: nonce,
            },
            success: function (result) {
                $this.closest('.updated').slideUp('fast', function () {
                    $this.remove();
                });
            },
            error: function (result) {
                $(".xli-error-message").html("Some error occurred. Please try again later");
                $(".xli-error-message").append(": " + result.message);
            }
        });
    });
});