(function ($, window, document) {
    'use strict';

    /**
     * Third-party SDKs (Facebook JS SDK, Twitter widgets.js) are no longer injected on every thank-you page.
     * They are loaded on demand, only when one of the social helpers below is actually called.
     * The legacy Universal Analytics loader (analytics.js) has been removed; GA4 tracking is printed separately.
     */
    window.xlwcty_with_fb = function (callback) {
        if (typeof window.FB !== 'undefined') {
            callback();
            return;
        }
        var prev_init = window.fbAsyncInit;
        window.fbAsyncInit = function () {
            if (typeof prev_init === 'function') {
                prev_init();
            } else {
                FB.init(xlwcty.social.fb);
            }
            callback();
        };
        if (document.getElementById('facebook-jssdk')) {
            return;
        }
        var js = document.createElement('script'), fjs = document.getElementsByTagName('script')[0];
        js.id = 'facebook-jssdk';
        js.async = true;
        js.src = 'https://connect.facebook.net/en_US/sdk.js';
        fjs.parentNode.insertBefore(js, fjs);
    };
    window.xlwcty_with_twttr = function (callback) {
        if (window.twttr && window.twttr.events) {
            callback();
            return;
        }
        window.twttr = (function (d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0], t = window.twttr || {};
            if (d.getElementById(id)) {
                return t;
            }
            js = d.createElement(s);
            js.id = id;
            js.src = "https://platform.twitter.com/widgets.js";
            fjs.parentNode.insertBefore(js, fjs);

            t._e = [];
            t.ready = function (f) {
                t._e.push(f);
            };

            return t;
        }(document, "script", "twitter-wjs"));
        if (typeof window.twttr.ready === 'function') {
            window.twttr.ready(callback);
        }
    };
    window.xlwcty_hooks = {
        hooks: {action: {}, filter: {}}, addAction: function (t, n, e, i) {
            xlwcty_hooks.addHook("action", t, n, e, i);
        }, addFilter: function (t, n, e, i) {
            xlwcty_hooks.addHook("filter", t, n, e, i);
        }, doAction: function (t) {
            xlwcty_hooks.doHook("action", t, arguments);
        }, applyFilters: function (t) {
            return xlwcty_hooks.doHook("filter", t, arguments);
        }, removeAction: function (t, n) {
            xlwcty_hooks.removeHook("action", t, n);
        }, removeFilter: function (t, n, e) {
            xlwcty_hooks.removeHook("filter", t, n, e);
        }, addHook: function (t, n, e, i, o) {
            void 0 == xlwcty_hooks.hooks[t][n] && (xlwcty_hooks.hooks[t][n] = []);// jshint ignore:line
            var r = xlwcty_hooks.hooks[t][n];
            void 0 == o && (o = n + "_" + r.length), xlwcty_hooks.hooks[t][n].push({tag: o, callable: e, priority: i});// jshint ignore:line
        }, doHook: function (t, n, e) {
            if (e = Array.prototype.slice.call(e, 1), void 0 != xlwcty_hooks.hooks[t][n]) {
                var i, o = xlwcty_hooks.hooks[t][n];
                o.sort(function (t, n) {
                    return t.priority - n.priority;
                });
                for (var r = 0; r < o.length; r++)
                    i = o[r].callable, "function" != typeof i && (i = window[i]), "action" == t ? i.apply(null, e) : e[0] = i.apply(null, e);// jshint ignore:line
            }
            return "filter" == t ? e[0] : void 0;
        }, removeHook: function (t, n, e, i) {
            if (void 0 != xlwcty_hooks.hooks[t][n])
                for (var o = xlwcty_hooks.hooks[t][n], r = o.length - 1; r >= 0; r--)
                    void 0 != i && i != o[r].tag || void 0 != e && e != o[r].priority || o.splice(r, 1);// jshint ignore:line
        }
    };
    window.maybeParseJson = function (data) {
        try {
            return JSON.parse(data);
        } catch (e) {
            return data;
        }
    };
    window.facebook_share = function (data, callback) {
        if (typeof window.FB === 'undefined') {
            xlwcty_with_fb(function () {
                facebook_share(data, callback);
            });
            return;
        }
        if (Object.keys(data).length == 0) {
            return false;
        }
        if (!data.hasOwnProperty("text")) {
            data.text = "";
        }
        if (data.href != "") {
            FB.ui({
                    method: 'share',
                    display: 'popup',
                    quote: data.text,
                    href: data.href,
                }, function (response) {
                    if (typeof callback == 'function') {
                        callback(response);
                    }
                }
            );
        }
    };
    window.facebook_like = function (callback_like, callback_dislike) {
        xlwcty_with_fb(function () {
            FB.Event.subscribe('edge.create', callback_like);
        });
    };

    window.twitter_follow = function (callback) {
        xlwcty_with_twttr(function () {
            twttr.events.bind('follow', function (event) {
                callback(event);
            });
        });
    };

    window.xlwcty_get_coupons = function (action, callback) {
        $.ajax({
            url: xlwcty.ajax_url,
            method: "post",
            data: {
                action: action,
                "cp_id": xlwcty.cp,
                "or_id": xlwcty.or
            }, success: function (resp) {
                resp = maybeParseJson(resp);
                if (typeof callback == "function") {
                    callback(resp);
                }
            }
        });
    };

    window.xlsetCookie = function (cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    };
    window.xlgetCookie = function (cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    };
    window.equalheight = function (container) {
        var currentTallest = 0,
            currentRowStart = 0,
            rowDivs = [],
            $el;
        jQuery(container).each(function () {
            var topPostion;
            $el = jQuery(this);
            jQuery($el).find('.xlwcty_pro_inner').height('auto');
            topPostion = $el.position().top;
            if (currentRowStart != topPostion) {
                for (var currentDiv = 0; currentDiv < rowDivs.length; currentDiv++) {
                    rowDivs[currentDiv].find('.xlwcty_pro_inner').height(currentTallest);
                }
                rowDivs.length = 0;
                currentRowStart = topPostion;
                currentTallest = $el.find('.xlwcty_pro_inner').height();
                rowDivs.push($el);

            } else {
                rowDivs.push($el);
                currentTallest = (currentTallest < $el.find('.xlwcty_pro_inner').height()) ? ($el.find('.xlwcty_pro_inner').height()) : (currentTallest);
            }
            for (var currentDiv1 = 0; currentDiv1 < rowDivs.length; currentDiv1++) {
                rowDivs[currentDiv1].find('.xlwcty_pro_inner').height(currentTallest);
            }
        });
    };
    $(document).ready(function () {
        if ($("#wp-admin-bar-xlwcty_admin_page_node-default").length > 0) {
            $("#wp-admin-bar-xlwcty_admin_page_node-default").html($(".xlwcty_header_passed").html());
        }

        if ($("body").hasClass("xlwcty_thankyou-template")) {
            // thank you single page

            // flatsome handling
            if ($(".checkout-breadcrumbs").length > 0) {
                if ($(".checkout-breadcrumbs").find("a.no-click").length > 0) {
                    $(".checkout-breadcrumbs").find("a.no-click").addClass("current");
                }
            }
        }

        if (typeof xlwcty_fab_ecom !== 'undefined' && xlwcty_fab_ecom) {
            // Tracking data is printed as a JS object literal; parse any legacy JSON-string values defensively.
            $.each(['fb_pa_data', 'products', 'gproducts'], function (i, key) {
                if (typeof xlwcty_fab_ecom[key] === 'string') {
                    xlwcty_fab_ecom[key] = maybeParseJson(xlwcty_fab_ecom[key]);
                }
            });
            if (xlwcty_fab_ecom.pixel_id > 0 && xlwcty_fab_ecom.pixel_id !== undefined) {
                !function (f, b, e, v, n, t, s) {
                    if (f.fbq)
                        return;
                    n = f.fbq = function () {
                        n.callMethod ?
                            n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                    };
                    if (!f._fbq)
                        f._fbq = n;
                    n.push = n;
                    n.loaded = !0;
                    n.version = '2.0';
                    n.queue = [];
                    t = b.createElement(e);
                    t.async = !0;
                    t.src = v;
                    s = b.getElementsByTagName(e)[0];
                    s.parentNode.insertBefore(t, s)
                }(window, document, 'script', '//connect.facebook.net/en_US/fbevents.js');

                if (xlwcty_fab_ecom.facebook_purchase_advanced_matching_event === 'on') {
                    if (xlwcty_fab_ecom.fb_pa_count > 0 && xlwcty_fab_ecom.fb_pa_count !== undefined) {
                        fbq('init', xlwcty_fab_ecom.pixel_id, xlwcty_fab_ecom.fb_pa_data);
                    }
                } else {
                    fbq('init', xlwcty_fab_ecom.pixel_id);
                }

                if (xlwcty_fab_ecom.facebook_tracking_event === 'on') {
                    fbq('track', 'PageView');
                }

                // Fire a single Purchase event even when both purchase toggles are on (contents are added when the
                // "purchase event" toggle is on) and pass the order id as eventID so Facebook can deduplicate it.
                if (xlwcty_fab_ecom.facebook_purchase_event === 'on' || xlwcty_fab_ecom.facebook_purchase_event_conversion === 'on') {
                    var xlwcty_purchase_data = {value: xlwcty_fab_ecom.order_total, currency: xlwcty_fab_ecom.currency};
                    if (xlwcty_fab_ecom.facebook_purchase_event === 'on') {
                        xlwcty_purchase_data.contents = xlwcty_fab_ecom.products;
                        xlwcty_purchase_data.content_type = 'product';
                    }
                    fbq('track', 'Purchase', xlwcty_purchase_data, {eventID: String(xlwcty_fab_ecom.order_id)});
                }
            }
        }
    });
    $(window).on('load', function () {
        if ($('.xlwcty_products li').length > 0) {
            equalheight('.xlwcty_products li');
        }
    });
    $(window).resize(function () {
        if ($('.xlwcty_products li').length > 0) {
            equalheight('.xlwcty_products li');
        }
    });
    $(window).on('storage onstorage', function (e) {
        if (xlwcty.hasOwnProperty('settings') == true && xlwcty.settings.hasOwnProperty('is_preview') == true) {
            if ('xlwcty_local_storage' === e.originalEvent.key && xlwcty.settings.is_preview == 'yes') {
                window.location.reload(true);
            }
        }
    });

})(jQuery, window, document);