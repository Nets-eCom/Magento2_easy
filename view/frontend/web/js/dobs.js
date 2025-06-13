!function () {
    "use strict";
    let e = function (e) {
        return e.CategoryBasedCheckout = "CategoryBasedCheckout",
            e.NexiRebranding = "NexiRebranding",
            e.ApplePay = "ApplePay",
            e.GooglePay = "GooglePay",
            e.Klarna = "Klarna",
            e.DoubleClick = "DoubleClick",
            e.PaymentCompletedLogging = "PaymentCompletedLogging",
            e.ClearSavedPayments = "ClearSavedPayments",
            e.RemoveRememberMe = "RemoveRememberMe",
            e.B2CWillNotBeRecognizedWithSSN = "B2CWillNotBeRecognizedWithSSN",
            e.SelectCardOnApplePayCancellation = "SelectCardOnApplePayCancellation",
            e.MobilePayCorrectPaymentType = "MobilePayCorrectPaymentType",
            e.KlarnaB2B = "KlarnaB2B",
            e.ApplePayUseMerchantCountry = "ApplePayUseMerchantCountry",
            e.ApplePayOnCancelDontAbort = "ApplePayOnCancelDontAbort",
            e.GooglePayUseAuthorizationFlow = "GooglePayUseAuthorizationFlow",
            e.GooglePayDynamicPriceUpdate = "GooglePayDynamicPriceUpdate",
            e.DisableConsumerLogin = "DisableConsumerLogin",
            e.EnableVippsForSweden = "EnableVippsForSweden",
            e.EasyPiecesCardInputSeparationEnabled = "EasyPiecesCardInputSeparationEnabled",
            e.SurchargeUI = "SurchargeUI",
            e.NewPaymentNotificationTexts = "NewPaymentNotificationTexts",
            e.NewApplePayButton = "NewApplePayButton",
            e.TopWindowFullRedirect = "TopWindowFullRedirect",
            e.RivertyCreditWarning = "RivertyCreditWarning",
            e
    }({});
    var t;
    Object.values(e);
    const n = ("string" == typeof (s = "false") ? "true" === s.toLowerCase() : s || !1) || !1;
    var s;
    null == (t = document) || null == (t = t.currentScript) || t.src;
    const i = "https://test.checkout.dibspayment.eu"
        , o = parseInt("13");
    let a, r;
    !function (t) {
        class s {
            constructor(e, t) {
                this.eventType = e,
                    this.data = t
            }

            toJson() {
                return JSON.stringify(this)
            }
        }

        t.Checkout = class {
            constructor(e) {
                this.iFrameDibsContainerId = "dibs-checkout-content",
                    this.iFrameDefaultContainerId = "nets-checkout-content",
                    this.iFrameContentStyleClass = "dibs-checkout-wrapper",
                    this.iFrameId = "nets-checkout-iframe",
                    this.applePayJSId = "applepayjs",
                    this.endPointDomain = void 0,
                    this.iFrameSrc = void 0,
                    this.styleSheetSrc = void 0,
                    this.paymentFailed = !1,
                    this.isApplePayEnabled = !1,
                    this.applePaySession = void 0,
                    this.applePayPaymentRequest = void 0,
                    this.allowedShippingCountries = void 0,
                    this.featureToggles = void 0,
                    this.onPaymentCompletedEvent = void 0,
                    this.onPaymentCancelledEvent = void 0,
                    this.onPayInitializedEvent = void 0,
                    this.onAddressChangedEvent = void 0,
                    this.onApplepayContactUpdatedEvent = void 0,
                    this.checkoutInitialized = !1,
                    this.isThemeSetExplicitly = !1,
                    this.options = void 0,
                    this.setupMessageListeners = e => {
                        if (!this.checkMsgSafe(e))
                            return;
                        try {
                            JSON.parse(e.data)
                        } catch (e) {
                            return void this.consoleLog(e)
                        }
                        const t = JSON.parse(e.data);
                        switch (this.consoleDebug(`Event received: ${t.eventType}, ${JSON.stringify(t.data)}`),
                            t.eventType) {
                            case "featureTogglesChanged":
                                console.log("SDK - Feature toggles changed");
                                this.featureToggles = t.data;
                                break;
                            case "checkoutInitialized":
                                console.log("SDK - Checkout initialized");
                                this.checkoutInitialized || (this.checkoutInitialized = !0,
                                    this.consoleLog("checkoutInitialized"),
                                    this.publishUnsentMessagesToCheckout(),
                                    this.postThemeToCheckout(),
                                    this.sendIframeSizing());
                                break;
                            case "goto3DS":
                                console.log("SDK - goto3DS");
                                this.goto3DS(t.data);
                                break;
                            case "payInitialized":
                                console.log("SDK - payInitialized");
                                this.onPayInitializedEvent ? this.onPayInitializedEvent(t.data) : (this.consoleLog("PaymentInitialized not handled by merchant"),
                                    this.sendPaymentOrderFinalizedEvent(!0));
                                break;
                            case "paymentSuccess":
                                console.log("SDK - paymentSuccess");
                                this.onPaymentCompletedEvent(t.data);
                                break;
                            case "paymentCancelled":
                                console.log("SDK - paymentCancelled");
                                this.onPaymentCancelledEvent(t.data);
                                break;
                            case "resize":
                                console.log("SDK - resize");
                                this.resizeIFrame(t.data);
                                break;
                            case "addressChanged":
                                console.log("SDK - addressChanged");
                                this.onAddressChangedEvent ? this.onAddressChangedEvent(t.data) : this.postMessage(new s("addressChangedNotHandled"));
                                break;
                            case "removePaymentFailedQueryParameter":
                                console.log("SDK - removePaymentFailedQueryParameter");
                                this.removePaymentFailedQueryParameter();
                                break;
                            case "inceptionIframeInitialized":
                                console.log("SDK - inceptionIframeInitialized");
                                this.inceptionIframeInitialized();
                                break;
                            case "getIsApplePaySupportedOnCurrentDevice":
                                console.log("SDK - getIsApplePaySupportedOnCurrentDevice");
                                this.getIsApplePaySupportedOnCurrentDevice();
                                break;
                            case "applePayClicked":
                                console.log("SDK - applePayClicked");
                                this.applePayClicked(t.data);
                                break;
                            case "applePaySessionValidated":
                                this.onReceivedMerchantSession(t.data);
                                break;
                            case "applePayPaymentComplete":
                                this.onApplePayPaymentComplete(t.data);
                                break;
                            case "setAllowedShippingCountries":
                                console.log("SDK - setAllowedShippingCountries");
                                this.onSetAllowedShippingCountries(t.data);
                                break;
                            default:

                                const n = t.eventType;

                                this.consoleLog(`unknown event ${n} ${JSON.stringify(e.data)}`)
                        }
                    }
                    ,
                    this.setupResizeListeners = () => {
                        const e = new s("resize");
                        this.postMessage(e)
                    }
                    ,
                    this.unsentMessages = [],
                    this.options = e,
                    this.init()
            }

            on(e, t) {
                if (!t)
                    throw new Error(`No function was supplied in the second argument. Please supply the function you want to be called on the ${e} event`);
                if ("pay-initialized" === e)
                    this.onPayInitializedEvent = t;
                else if ("payment-completed" === e)
                    this.onPaymentCompletedEvent = t;
                else if ("payment-cancelled" === e)
                    this.onPaymentCancelledEvent = t;
                else if ("address-changed" === e)
                    this.onAddressChangedEvent = t;
                else if ("applepay-contact-updated" === e)
                    this.onApplepayContactUpdatedEvent = t;
                else {
                    const t = e;
                    this.consoleLog(`${t} is not a valid public event name.`)
                }
            }

            send(e, t) {
                if ("payment-order-finalized" === e) {
                    const e = t || !1;
                    this.sendPaymentOrderFinalizedEvent(e)
                } else
                    "payment-cancel-initiated" === e && this.postMessage(new s("cancelPayment"))
            }

            freezeCheckout() {
                this.postMessage(new s("freezeCheckout"))
            }

            thawCheckout() {
                this.postMessage(new s("thawCheckout"))
            }

            setTheme(e) {
                this.isThemeSetExplicitly = !0,
                    this.postMessage(new s("setTheme", e))
            }

            setLanguage(e) {
                this.postMessage(new s("setLanguage", e))
            }

            completeApplePayShippingContactUpdate(e) {
                if (this.isApplePayEnabled && this.applePaySession && this.applePayPaymentRequest)
                    try {
                        const t = this.applePayPaymentRequest.total;
                        if (!e) {
                            this.consoleLog("Does not support this operation. Undefined amount specified.");
                            const e = new ApplePayError("unknown", void 0, " Undefined amount specified.");
                            return void this.applePaySession.completeShippingContactSelection({
                                newTotal: t,
                                errors: [e]
                            })
                        }
                        if ("string" != typeof e && "number" != typeof e) {
                            this.consoleLog("Does not support this operation. Wrong argument type provided.");
                            const e = new ApplePayError("unknown", void 0, "Wrong argument type provided.");
                            return void this.applePaySession.completeShippingContactSelection({
                                newTotal: t,
                                errors: [e]
                            })
                        }
                        "number" == typeof e && (e = String(e)),
                            this.consoleLog(`Apple pay order amount update with ${e}`);
                        const n = new s("updateApplePayOrderAmount", e);
                        this.postMessage(n);
                        const {label: i, type: o} = this.applePayPaymentRequest.total
                            , a = Number(e) / 100;
                        this.applePaySession.completeShippingContactSelection({
                            newTotal: {
                                amount: String(a),
                                label: i,
                                type: o
                            }
                        })
                    } catch (e) {
                        this.consoleError(e, "Error in completeShippingMethodSelection for ApplePay")
                    }
                else
                    this.consoleLog("Does not support this operation. ApplePay is disabled.")
            }

            cleanup() {
                this.removeListeners()
            }

            init() {
                var e, t, n, s, o, a;
                const r = null == (e = this.options) ? void 0 : e.checkoutKey
                    , p = null == (t = this.options) ? void 0 : t.paymentId
                    , l = this;
                if (this.options.containerId || (this.options.containerId = document.getElementById(this.iFrameDefaultContainerId) ? this.iFrameDefaultContainerId : this.iFrameDibsContainerId),
                !this.isThemeSet() && i && r && p) {
                    const e = new XMLHttpRequest;
                    e.addEventListener("load", (function () {
                            if (200 === this.status && !l.isThemeSetExplicitly) {
                                const e = JSON.parse(this.responseText);
                                l.options.theme = e,
                                    l.postThemeToCheckout()
                            }
                        }
                    )),
                        e.open("GET", `${i}/api/v1/theming/checkout`),
                        e.setRequestHeader("CheckoutKey", r),
                        e.setRequestHeader("PaymentId", p),
                        e.send()
                }
                this.paymentFailed = "true" === this.getQueryStringParameter("paymentFailed", window.location.href),
                    this.endPointDomain = "https://test.checkout.dibspayment.eu",
                    this.iFrameSrc = `${this.endPointDomain}/v1/?checkoutKey=${null == (n = this.options) ? void 0 : n.checkoutKey}&paymentId=${null == (s = this.options) ? void 0 : s.paymentId}`,
                null != (o = this.options) && o.partnerMerchantNumber && (this.iFrameSrc += `&partnerMerchantNumber=${this.options.partnerMerchantNumber}`),
                null != (a = this.options) && a.language && (this.iFrameSrc += `&language=${this.options.language}`),
                this.paymentFailed && (this.iFrameSrc += `&paymentFailed=${this.paymentFailed}`),
                    this.styleSheetSrc = `${this.endPointDomain}/v1/assets/css/checkout.css`,
                    this.setListeners();
                const h = document.getElementsByTagName("head")[0];
                this.addStyleSheet(h),
                    this.addMainIFrame()
            }

            isWindowOnTopLevel() {
                try {
                    return window.top.location.href,
                        !0
                } catch (e) {
                    return !1
                }
            }

            isThemeSet() {
                var e;
                return !(null == (e = this.options) || !e.theme) && Object.keys(this.options.theme).length > 0
            }

            inceptionIframeInitialized() {
                if (this.isWindowOnTopLevel()) {
                    var e;
                    const t = this.getIFrameHeight()
                        , n = null == (e = window.top) ? void 0 : e.innerHeight;
                    if (n && t > n) {
                        this.resizeIFrame(n);
                        const e = new s("scrollIntoView", n);
                        this.postMessage(e)
                    }
                }
            }

            getErrorMsg(e, t) {
                let n = t;
                return "string" == typeof e ? n = `${t} ${e}` : e instanceof Error && (n = `${t} ${e.message}`),
                    n
            }

            loadApplePayJs(t) {
                const n = document.getElementById(this.applePayJSId);
                if (!n) {
                    const n = document.createElement("script");
                    this.isFeatureToggleEnabled(e.NewApplePayButton) ? n.src = "https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js" : n.src = "https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js",
                        n.id = this.applePayJSId,
                        n.defer = !0,
                        n.onload = () => {
                            this.consoleLog("Loaded applepay js script"),
                                t()
                        }
                        ,
                        n.onerror = () => {
                            this.consoleLog("Apple Pay SDK cannot be loaded", !0)
                        }
                        ,
                        document.body.appendChild(n)
                }
                n && t && t()
            }

            getIsApplePaySupportedOnCurrentDevice() {
                this.loadApplePayJs((() => {
                        try {
                            const e = window.ApplePaySession;
                            if (e) {
                                const t = e.supportsVersion(o)
                                    , n = e && e.canMakePayments();
                                t || this.consoleLog("Does not support applepay version : " + o, !0),
                                n || this.consoleLog("Cannot make Apple payments", !0),
                                    this.isApplePayEnabled = e && n && t;
                                const i = new s("setIsApplePaySupportedOnCurrentDevice", this.isApplePayEnabled);
                                this.consoleLog("Apple pay enabled : " + this.isApplePayEnabled),
                                    this.postMessage(i)
                            } else {
                                this.consoleLog("Empty applepay session on the window", !0);
                                const e = new s("setIsApplePaySupportedOnCurrentDevice", !1);
                                this.postMessage(e)
                            }
                        } catch (e) {
                            this.consoleError(e, "Something went wrong. Apple pay disabled.");
                            const t = new s("setIsApplePaySupportedOnCurrentDevice", !1);
                            this.postMessage(t)
                        }
                    }
                ))
            }

            isFeatureToggleEnabled(e) {
                var t, n;
                return null != (t = null == (n = this.featureToggles) || null == (n = n.find((t => t.name === e))) ? void 0 : n.isEnabled) && t
            }

            applePayClicked(e) {
                try {
                    this.applePayPaymentRequest = e,
                        this.applePaySession = new window.ApplePaySession(o, e),
                        this.applePaySession.onvalidatemerchant = this.getOnValidateMerchant(),
                        this.applePaySession.onpaymentauthorized = this.getOnPaymentAuthorized(),
                        this.applePaySession.oncancel = this.getOnCancel(),
                        this.applePaySession.onshippingcontactselected = this.getOnShippingContactSelected(),
                        this.applePaySession.begin()
                } catch (e) {
                    this.consoleError(e, "Apple pay clicked. Something went wrong.")
                }
            }

            abortApplePay() {
                try {
                    this.applePaySession && this.applePaySession.abort()
                } catch (e) {
                    this.consoleError(e, "Apple pay abort. Something went wrong.")
                }
            }

            getOnCancel() {
                return t => {
                    const n = new s("onApplePayWasCanceled");
                    this.postMessage(n),
                        this.isFeatureToggleEnabled(e.ApplePayOnCancelDontAbort) ? this.consoleLog("Apple pay cancelled.") : this.abortApplePay()
                }
            }

            getOnShippingContactSelected() {
                return e => {
                    var t, n;
                    const s = this.applePayPaymentRequest.total
                        , i = null == e || null == (t = e.shippingContact) ? void 0 : t.countryCode;
                    if (!i) {
                        const e = new ApplePayError("addressUnserviceable", "country");
                        return e.message = "Country is missing in shipping address",
                            void this.applePaySession.completeShippingContactSelection({
                                newTotal: s,
                                errors: [e]
                            })
                    }
                    if ((null == (n = this.allowedShippingCountries) ? void 0 : n.length) > 0 && !this.allowedShippingCountries.includes(i)) {
                        const e = new ApplePayError("addressUnserviceable", "country");
                        return e.message = "Country specified in shipping address is not supported",
                            void this.applePaySession.completeShippingContactSelection({
                                newTotal: s,
                                errors: [e]
                            })
                    }
                    if (this.onApplepayContactUpdatedEvent)
                        return void this.onApplepayContactUpdatedEvent({
                            postalCode: e.shippingContact.postalCode,
                            countryCode: e.shippingContact.countryCode
                        });
                    const o = new ApplePayError("unknown", void 0);
                    o.message = "Applepay contact update handler missing.",
                        this.applePaySession.completeShippingContactSelection({
                            newTotal: s,
                            errors: [o]
                        })
                }
            }

            getOnPaymentAuthorized() {
                return e => {
                    var t, n, i, o;
                    if ((e => null == e || 0 === e.length || "{}" === JSON.stringify(e))(null == e || null == (t = e.payment) ? void 0 : t.token))
                        return this.consoleLog(`Apple Pay ${"object" == typeof (null == e ? void 0 : e.payment) && 0 === Object.keys(null == e ? void 0 : e.payment).length ? "payment" : "token"} is missing`, !0),
                            void this.applePaySession.completePayment({
                                status: ApplePaySession.STATUS_FAILURE,
                                errors: [new ApplePayError("unknown", void 0, "Payment data is empty")]
                            });
                    if (null != (n = e.payment.shippingContact) && n.countryCode && (null == (i = this.allowedShippingCountries) ? void 0 : i.length) > 0 && !this.allowedShippingCountries.includes(null == (o = e.payment.shippingContact) ? void 0 : o.countryCode))
                        return this.consoleLog("Country specified in shipping address is not supported", !0),
                            void this.applePaySession.completePayment({
                                status: ApplePaySession.STATUS_FAILURE,
                                errors: [new ApplePayError("addressUnserviceable", "country", "Country specified in shipping address is not supported")]
                            });
                    const a = new s("authorizeApplePay", e.payment);
                    this.postMessage(a)
                }
            }

            getOnValidateMerchant() {
                return e => {
                    const t = new s("validateApplePaySession");
                    this.postMessage(t)
                }
            }

            onReceivedMerchantSession(e) {
                try {
                    this.applePaySession.completeMerchantValidation(e)
                } catch (e) {
                    this.consoleError(e, "Something went wrong while validating merchant session"),
                        this.abortApplePay()
                }
            }

            onApplePayPaymentComplete(e) {
                try {
                    const t = {
                        status: Boolean(e).valueOf() ? ApplePaySession.STATUS_SUCCESS : ApplePaySession.STATUS_FAILURE
                    };
                    this.applePaySession.completePayment(t)
                } catch (e) {
                    this.consoleError(e, "Something went wrong while completing AppleyPay payment."),
                        this.abortApplePay()
                }
            }

            onSetAllowedShippingCountries(e) {
                this.allowedShippingCountries = e
            }

            addStyleSheet(e) {
                const t = document.createElement("link");
                t.rel = "stylesheet",
                    t.type = "text/css",
                    t.href = this.styleSheetSrc,
                    e.appendChild(t),
                    this.consoleLog("Added stylesheet script " + t.href)
            }

            addMainIFrame() {
                const e = document.createElement("iframe");
                e.id = this.iFrameId,
                    e.src = this.iFrameSrc,
                    e.referrerPolicy = "strict-origin-when-cross-origin",
                    e.allow = "payment *";
                const t = document.getElementById(this.options.containerId);
                null !== t && (t.setAttribute("class", this.iFrameContentStyleClass),
                    t.appendChild(e),
                    this.consoleLog("Added main IFrame script to " + this.options.containerId)),
                    e.onload = () => {
                        this.consoleLog("iframe ready")
                    }
                    ,
                    e.allowTransparency = "true",
                    e.frameBorder = "0",
                    e.scrolling = "no"
            }

            postThemeToCheckout() {
                this.options.theme && this.setTheme(this.options.theme)
            }

            goto3DS(e) {
                const t = e
                    , n = document.createElement("div");
                n.style.display = "none",
                    n.innerHTML = t.form,
                    document.body.appendChild(n);
                const s = document.getElementById(t.formId);
                null !== s && s.submit()
            }

            resizeIFrame(e) {
                const t = `${e}px`;
                this.getIframe().height = t
            }

            sendIframeSizing() {
                const e = new s("initialIframeSize", this.getIFrameSize());
                this.postMessage(e)
            }

            getIFrameSize() {
                const e = this.getIframe()
                    , {offsetWidth: t, offsetHeight: n} = e;
                return {
                    width: t,
                    height: n
                }
            }

            getIFrameHeight() {
                const e = this.getIframe().height;
                return parseInt(e.split("px")[0]) || 0
            }

            sendPaymentOrderFinalizedEvent(e) {
                const t = new s("paymentOrderFinalized", e);
                this.postMessage(t)
            }

            removeListeners() {
                window.removeEventListener("message", this.setupMessageListeners),
                    window.removeEventListener("resize", this.setupResizeListeners)
            }

            setListeners() {
                window.addEventListener("message", this.setupMessageListeners, !1),
                    window.addEventListener("resize", this.setupResizeListeners)
            }

            removePaymentFailedQueryParameter() {
                const e = new URLSearchParams(window.location.search)
                    , t = "paymentFailed";
                if (e.has(t)) {
                    e.delete(t);
                    const n = e.toString()
                        , s = `${location.origin}${location.pathname}?${n}`;
                    window.history.replaceState(void 0, document.title, s)
                }
            }

            checkMsgSafe(e) {
                const t = e.origin;
                return void 0 === t ? (this.consoleDebug(`Checkout: unknown origin ${JSON.stringify(t)} (${JSON.stringify(e)}, ${JSON.stringify(e.data)})`),
                    !1) : !(e.data && "react-devtools-bridge" === e.data.source || t !== this.endPointDomain && (this.consoleDebug(`Checkout: unknown origin ${JSON.stringify(t)} (${JSON.stringify(e)}, ${JSON.stringify(e.data)})`),
                    1))
            }

            getQueryStringParameter(e, t) {
                if (t = t || "",
                0 === (e = e || "").length || 0 === t.length)
                    return "";
                const n = new RegExp("[?&]" + e + "=([^&#]*)", "i").exec(t);
                return n ? n[1] : ""
            }

            consoleDebug(e) {
                n && console.debug(e)
            }

            consoleLog(e, t) {
                t ? this.postMessage(new s("logErrorMessage", e)) : n && console.log(e)
            }

            consoleError(e, t) {
                const {name: i, stack: o} = e
                    , a = this.getErrorMsg(e, t);
                this.postMessage(new s("logErrorMessage", {
                    name: i,
                    message: a,
                    stack: o
                })),
                n && console.error(a)
            }

            getIframe() {
                return document.getElementById(this.iFrameId)
            }

            postMessage(e) {
                const t = this.getIframe();
                null != t && t.contentWindow && this.checkoutInitialized ? t.contentWindow.postMessage(null == e ? void 0 : e.toJson(), this.endPointDomain) : e && this.unsentMessages.push(e)
            }

            publishUnsentMessagesToCheckout() {
                const e = this.getIframe();
                for (; this.unsentMessages.length;) {
                    var t, n;
                    null == e || null == (t = e.contentWindow) || t.postMessage(null == (n = this.unsentMessages.shift()) ? void 0 : n.toJson(), this.endPointDomain)
                }
            }
        }
            ,
            window.Nets = a
    }(a || (a = {})),
    r || (r = {}),
        a.Checkout,
        window.Dibs = a
}();
