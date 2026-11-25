var xlwctyCore = {loadmap: false};
(function ($) {
    var xlwctyMap = {};
    var xlwctyMarker = {};

    function infobubble_init() {
        $.getScript(xlwcty.infobubble_url, function (data, textStatus, jqxhr) {
            map_init();
        });
    }

    window.map_init = function () {
        var infoBubble;

        if (typeof google === "undefined" && typeof xlwcty.settings.is_preview != "undefined" && xlwcty.settings.is_preview === 'yes') {
            $(".xlwcty-map-component").html("<div class='xlwcty_map_error_txt'>Google Map Api Key Not Found</div>");
            return;
        }
        if (typeof google === "undefined") {
            return true;
        }
        var geocoder = new google.maps.Geocoder();
        var styles = {
            "standard": [],
            "grey": [{"elementType": "geometry", "stylers": [{"color": "#f5f5f5"}]}, {"elementType": "labels.icon", "stylers": [{"visibility": "off"}]}, {
                "elementType": "labels.text.fill", "stylers": [{"color": "#616161"}]
            }, {"elementType": "labels.text.stroke", "stylers": [{"color": "#f5f5f5"}]}, {
                "featureType": "administrative.land_parcel", "elementType": "labels.text.fill", "stylers": [{"color": "#bdbdbd"}]
            }, {"featureType": "poi", "elementType": "geometry", "stylers": [{"color": "#eeeeee"}]}, {
                "featureType": "poi", "elementType": "labels.text.fill", "stylers": [{"color": "#757575"}]
            }, {"featureType": "poi.park", "elementType": "geometry", "stylers": [{"color": "#e5e5e5"}]}, {
                "featureType": "poi.park", "elementType": "labels.text.fill", "stylers": [{"color": "#9e9e9e"}]
            }, {"featureType": "road", "elementType": "geometry", "stylers": [{"color": "#ffffff"}]}, {
                "featureType": "road.arterial", "elementType": "labels.text.fill", "stylers": [{"color": "#757575"}]
            }, {"featureType": "road.highway", "elementType": "geometry", "stylers": [{"color": "#dadada"}]}, {
                "featureType": "road.highway", "elementType": "labels.text.fill", "stylers": [{"color": "#616161"}]
            }, {"featureType": "road.local", "elementType": "labels.text.fill", "stylers": [{"color": "#9e9e9e"}]}, {
                "featureType": "transit.line", "elementType": "geometry", "stylers": [{"color": "#e5e5e5"}]
            }, {"featureType": "transit.station", "elementType": "geometry", "stylers": [{"color": "#eeeeee"}]}, {
                "featureType": "water", "elementType": "geometry", "stylers": [{"color": "#c9c9c9"}]
            }, {"featureType": "water", "elementType": "labels.text.fill", "stylers": [{"color": "#9e9e9e"}]}],
            "retro": [{"elementType": "geometry", "stylers": [{"color": "#ebe3cd"}]}, {"elementType": "labels.text.fill", "stylers": [{"color": "#523735"}]}, {
                "elementType": "labels.text.stroke", "stylers": [{"color": "#f5f1e6"}]
            }, {"featureType": "administrative", "elementType": "geometry.stroke", "stylers": [{"color": "#c9b2a6"}]}, {
                "featureType": "administrative.land_parcel", "elementType": "geometry.stroke", "stylers": [{"color": "#dcd2be"}]
            }, {"featureType": "administrative.land_parcel", "elementType": "labels.text.fill", "stylers": [{"color": "#ae9e90"}]}, {
                "featureType": "landscape.natural", "elementType": "geometry", "stylers": [{"color": "#dfd2ae"}]
            }, {"featureType": "poi", "elementType": "geometry", "stylers": [{"color": "#dfd2ae"}]}, {
                "featureType": "poi", "elementType": "labels.text.fill", "stylers": [{"color": "#93817c"}]
            }, {"featureType": "poi.park", "elementType": "geometry.fill", "stylers": [{"color": "#a5b076"}]}, {
                "featureType": "poi.park", "elementType": "labels.text.fill", "stylers": [{"color": "#447530"}]
            }, {"featureType": "road", "elementType": "geometry", "stylers": [{"color": "#f5f1e6"}]}, {
                "featureType": "road.arterial", "elementType": "geometry", "stylers": [{"color": "#fdfcf8"}]
            }, {"featureType": "road.highway", "elementType": "geometry", "stylers": [{"color": "#f8c967"}]}, {
                "featureType": "road.highway", "elementType": "geometry.stroke", "stylers": [{"color": "#e9bc62"}]
            }, {"featureType": "road.highway.controlled_access", "elementType": "geometry", "stylers": [{"color": "#e98d58"}]}, {
                "featureType": "road.highway.controlled_access", "elementType": "geometry.stroke", "stylers": [{"color": "#db8555"}]
            }, {"featureType": "road.local", "elementType": "labels.text.fill", "stylers": [{"color": "#806b63"}]}, {
                "featureType": "transit.line", "elementType": "geometry", "stylers": [{"color": "#dfd2ae"}]
            }, {"featureType": "transit.line", "elementType": "labels.text.fill", "stylers": [{"color": "#8f7d77"}]}, {
                "featureType": "transit.line", "elementType": "labels.text.stroke", "stylers": [{"color": "#ebe3cd"}]
            }, {"featureType": "transit.station", "elementType": "geometry", "stylers": [{"color": "#dfd2ae"}]}, {
                "featureType": "water", "elementType": "geometry.fill", "stylers": [{"color": "#b9d3c2"}]
            }, {"featureType": "water", "elementType": "labels.text.fill", "stylers": [{"color": "#92998d"}]}],
            "light": [{"featureType": "water", "elementType": "geometry", "stylers": [{"color": "#e9e9e9"}, {"lightness": 17}]}, {
                "featureType": "landscape", "elementType": "geometry", "stylers": [{"color": "#f5f5f5"}, {"lightness": 20}]
            }, {"featureType": "road.highway", "elementType": "geometry.fill", "stylers": [{"color": "#ffffff"}, {"lightness": 17}]}, {
                "featureType": "road.highway", "elementType": "geometry.stroke", "stylers": [{"color": "#ffffff"}, {"lightness": 29}, {"weight": 0.2}]
            }, {"featureType": "road.arterial", "elementType": "geometry", "stylers": [{"color": "#ffffff"}, {"lightness": 18}]}, {
                "featureType": "road.local", "elementType": "geometry", "stylers": [{"color": "#ffffff"}, {"lightness": 16}]
            }, {"featureType": "poi", "elementType": "geometry", "stylers": [{"color": "#f5f5f5"}, {"lightness": 21}]}, {
                "featureType": "poi.park", "elementType": "geometry", "stylers": [{"color": "#dedede"}, {"lightness": 21}]
            }, {"elementType": "labels.text.stroke", "stylers": [{"visibility": "on"}, {"color": "#ffffff"}, {"lightness": 16}]}, {
                "elementType": "labels.text.fill", "stylers": [{"saturation": 36}, {"color": "#333333"}, {"lightness": 40}]
            }, {"elementType": "labels.icon", "stylers": [{"visibility": "off"}]}, {
                "featureType": "transit", "elementType": "geometry", "stylers": [{"color": "#f2f2f2"}, {"lightness": 19}]
            }, {"featureType": "administrative", "elementType": "geometry.fill", "stylers": [{"color": "#fefefe"}, {"lightness": 20}]}, {
                "featureType": "administrative", "elementType": "geometry.stroke", "stylers": [{"color": "#fefefe"}, {"lightness": 17}, {"weight": 1.2}]
            }],
            "blue-essence": [{"featureType": "landscape.natural", "elementType": "geometry.fill", "stylers": [{"visibility": "on"}, {"color": "#e0efef"}]}, {
                "featureType": "poi", "elementType": "geometry.fill", "stylers": [{"visibility": "on"}, {"hue": "#1900ff"}, {"color": "#c0e8e8"}]
            }, {"featureType": "road", "elementType": "geometry", "stylers": [{"lightness": 100}, {"visibility": "simplified"}]}, {
                "featureType": "road", "elementType": "labels", "stylers": [{"visibility": "off"}]
            }, {"featureType": "transit.line", "elementType": "geometry", "stylers": [{"visibility": "on"}, {"lightness": 700}]}, {
                "featureType": "water", "elementType": "all", "stylers": [{"color": "#7dcdcd"}]
            }],
            "facebook": [{"featureType": "water", "elementType": "all", "stylers": [{"color": "#3b5998"}]}, {
                "featureType": "administrative.province", "elementType": "all", "stylers": [{"visibility": "off"}]
            }, {"featureType": "all", "elementType": "all", "stylers": [{"hue": "#3b5998"}, {"saturation": -22}]}, {
                "featureType": "landscape", "elementType": "all", "stylers": [{"visibility": "on"}, {"color": "#f7f7f7"}, {"saturation": 10}, {"lightness": 76}]
            }, {"featureType": "landscape.natural", "elementType": "all", "stylers": [{"color": "#f7f7f7"}]}, {
                "featureType": "road.highway", "elementType": "all", "stylers": [{"color": "#8b9dc3"}]
            }, {"featureType": "administrative.country", "elementType": "geometry.stroke", "stylers": [{"visibility": "simplified"}, {"color": "#3b5998"}]}, {
                "featureType": "road.highway", "elementType": "all", "stylers": [{"visibility": "on"}, {"color": "#8b9dc3"}]
            }, {"featureType": "road.highway", "elementType": "all", "stylers": [{"visibility": "simplified"}, {"color": "#8b9dc3"}]}, {
                "featureType": "transit.line", "elementType": "all", "stylers": [{"invert_lightness": false}, {"color": "#ffffff"}, {"weight": 0.43}]
            }, {"featureType": "road.highway", "elementType": "labels.icon", "stylers": [{"visibility": "off"}]}, {
                "featureType": "road.local", "elementType": "geometry.fill", "stylers": [{"color": "#8b9dc3"}]
            }, {"featureType": "administrative", "elementType": "labels.icon", "stylers": [{"visibility": "on"}, {"color": "#3b5998"}]}],
            "muted-blue": [{"featureType": "all", "stylers": [{"saturation": 0}, {"hue": "#e7ecf0"}]}, {"featureType": "road", "stylers": [{"saturation": -70}]}, {
                "featureType": "transit", "stylers": [{"visibility": "off"}]
            }, {"featureType": "poi", "stylers": [{"visibility": "off"}]}, {"featureType": "water", "stylers": [{"visibility": "simplified"}, {"saturation": -60}]}],
            "mid-night": [{"featureType": "all", "elementType": "labels.text.fill", "stylers": [{"color": "#ffffff"}]}, {
                "featureType": "all", "elementType": "labels.text.stroke", "stylers": [{"color": "#000000"}, {"lightness": 13}]
            }, {"featureType": "administrative", "elementType": "geometry.fill", "stylers": [{"color": "#000000"}]}, {
                "featureType": "administrative", "elementType": "geometry.stroke", "stylers": [{"color": "#144b53"}, {"lightness": 14}, {"weight": 1.4}]
            }, {"featureType": "landscape", "elementType": "all", "stylers": [{"color": "#08304b"}]}, {
                "featureType": "poi", "elementType": "geometry", "stylers": [{"color": "#0c4152"}, {"lightness": 5}]
            }, {"featureType": "road.highway", "elementType": "geometry.fill", "stylers": [{"color": "#000000"}]}, {
                "featureType": "road.highway", "elementType": "geometry.stroke", "stylers": [{"color": "#0b434f"}, {"lightness": 25}]
            }, {"featureType": "road.arterial", "elementType": "geometry.fill", "stylers": [{"color": "#000000"}]}, {
                "featureType": "road.arterial", "elementType": "geometry.stroke", "stylers": [{"color": "#0b3d51"}, {"lightness": 16}]
            }, {"featureType": "road.local", "elementType": "geometry", "stylers": [{"color": "#000000"}]}, {
                "featureType": "transit", "elementType": "all", "stylers": [{"color": "#146474"}]
            }, {"featureType": "water", "elementType": "all", "stylers": [{"color": "#021019"}]}]
        };
        if ($(".xlwcty-map-component").length > 0) {
            var mapIncrement = 0;
            $(".xlwcty-map-component").each(function () {
                var mapData = {};
                var $this = $(this);
                var mapIndex = mapIncrement;
                mapData.address = $this.attr("data-address") || "";
                mapData.icon = $this.attr("data-nm-icon") || "";
                mapData.zoom = parseInt($this.attr("data-zoom-level"));
                mapData.marker_text = document.createElement("div");
                mapData.marker_text.className = "xlwctymarkertext";
                mapData.marker_text.appendChild(document.createTextNode($this.attr("data-marker-text") || ""));
                var style = $this.attr("data-style");

                var style_data = [];
                if (style != "" && styles.hasOwnProperty(style) == true) {
                    style_data = styles[style];
                }
                geocoder.geocode({'address': mapData.address}, function (results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        var latitude = results[0].geometry.location.lat();
                        var longitude = results[0].geometry.location.lng();
                        mapData.lat = latitude;
                        mapData.lng = longitude;

                        if (mapData.lat != '' && mapData.lng != '') {
                            mapData.center = {lat: mapData.lat, lng: mapData.lng};
                            var mapOptions = {
                                center: mapData.center, zoom: mapData.zoom, styles: style_data
                            };
                            mapOptions = xlwcty_hooks.applyFilters("xlwcty_map_options", mapOptions);
                            xlwctyMap[mapIndex] = new google.maps.Map($this[0], mapOptions);
                            var markerData = {
                                map: xlwctyMap[mapIndex], position: mapData.center, title: mapData.address, content: mapData.marker_text
                            };
                            if (mapData.icon != "") {
                                markerData.icon = mapData.icon;
                            }
                            if (mapData.address != "") {
                                markerData.address = mapData.address;
                            }

                            markerData = xlwcty_hooks.applyFilters("xlwcty_marker_data", markerData, xlwctyMap[mapIndex]);
                            var inforpopup = new InfoBubble({
                                minWidth: 200, maxHeight: 200, minHeight: 120, content: markerData.content, closeSrc: xlwcty.plugin_url + '/assets/img/close.png',
                            });
                            xlwctyMarker[mapIndex] = new google.maps.Marker(markerData);
                            google.maps.event.addListener(xlwctyMarker[mapIndex], 'click', function () {
                                inforpopup.open(xlwctyMap[mapIndex], xlwctyMarker[mapIndex]);
                                xlwctyMap[mapIndex].panTo(xlwctyMarker[mapIndex].getPosition());
                                xlwctyMap[mapIndex].setCenter(xlwctyMarker[mapIndex].getPosition());
                            });
                        }
                    } else {
                        var errorMsg = '';
                        if (status == google.maps.GeocoderStatus.ERROR || status == google.maps.GeocoderStatus.UNKNOWN_ERROR) {
                            errorMsg = xlwcty.map_errors.error;
                        } else if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                            errorMsg = xlwcty.map_errors.over_limit;
                        } else if (status == google.maps.GeocoderStatus.REQUEST_DENIED) {
                            errorMsg = xlwcty.map_errors.request_denied;
                        } else if (xlwcty.settings && typeof xlwcty.settings.google_map_error_txt === 'string' && xlwcty.settings.google_map_error_txt !== '') {
                            errorMsg = xlwcty.settings.google_map_error_txt;
                        }
                        if (!errorMsg && xlwcty.map_errors && xlwcty.map_errors.error) {
                            errorMsg = xlwcty.map_errors.error;
                        }
                        $this.empty().append($('<div/>', {'class': 'xlwcty_map_error_txt', 'text': errorMsg}));
                    }
                });
                mapIncrement++;
            });
        }
    };

    xlwctyCore.loadmap = function () {
        if (typeof google != "undefined" && google.hasOwnProperty("maps") === true && xlwcty_is_google_map_failed == false) {
            if (xlwcty.infobubble_url != "" && typeof InfoBubble !== 'function') {
                infobubble_init();
            } else {
                map_init();
            }
        } else {
            if (xlwcty.google_map_key != "") {
                $.getScript("//maps.googleapis.com/maps/api/js?key=" + xlwcty.google_map_key, function (data, textStatus, jqxhr) {
                    if (jqxhr.status === 200) {
                        var old_google = google;
                        infobubble_init();
                    }
                });
            }
        }
    };

    xlwctyCore.loadmap();

})(jQuery);
;(function ($, window, document) {
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
                for (var r = 0; r < o.length; r++) i = o[r].callable, "function" != typeof i && (i = window[i]), "action" == t ? i.apply(null, e) : e[0] = i.apply(null, e);// jshint ignore:line
            }
            return "filter" == t ? e[0] : void 0;
        }, removeHook: function (t, n, e, i) {
            if (void 0 != xlwcty_hooks.hooks[t][n]) for (var o = xlwcty_hooks.hooks[t][n], r = o.length - 1; r >= 0; r--) void 0 != i && i != o[r].tag || void 0 != e && e != o[r].priority || o.splice(r, 1);// jshint ignore:line
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
                method: 'share', display: 'popup', quote: data.text, href: data.href,
            }, function (response) {
                if (typeof callback == 'function') {
                    callback(response);
                }
            });
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
            url: xlwcty.ajax_url, method: "post", data: {
                action: action, "cp_id": xlwcty.cp, "or_id": xlwcty.or
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
        var currentTallest = 0, currentRowStart = 0, rowDivs = [], $el;
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
                    if (f.fbq) return;
                    n = f.fbq = function () {
                        n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                    };
                    if (!f._fbq) f._fbq = n;
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