define([
    'jquery',
    'uiComponent',
    'ko',
    'mage/url',
    'mage/translate',
    'underscore',
    'Lordhair_LoginSignup/js/knockout.validation.min'
], function($, Component, ko, url, $t, _) {
    "use strict";
    url.setBaseUrl(BASE_URL)
    const getSiteUrl = url.build('');
    const gePubMediaLink = url.build('media/login-lignup');
    const lordAjaxLogin = url.build('lordajaxloginsign/ajax/login');
    const lordAjaxLoginOtp = url.build('lordajaxloginsign/ajax/loginwithotp');
    const lordAjaxSendOtp = url.build('lordajaxloginsign/ajax/sendotp');
    const lordAjaxRegister = url.build('lordajaxloginsign/ajax/register');
    const lordAjaxOtpVerify = url.build('lordajaxloginsign/ajax/otpverify');
    const lordAjaxCheckLoggedIn = url.build('lordajaxloginsign/ajax/checkloggedin');
    const lordAjaxVerifyEmailOtp = url.build('lordajaxloginsign/ajax/emailverifylogin');
    const lordAjaxResendEmailOtp = url.build('lordajaxloginsign/ajax/resendemailotp');
    const lordAjaxSignUpWithFb = url.build('lordajaxloginsign/ajax/signupwithfb');
    const lordAjaxLogout = url.build('lordajaxloginsign/ajax/logout');
    const lordAjaxForgotEmailOtp = url.build('lordajaxloginsign/ajax/forgotEmailOtp');
    const lordAjaxVerifyForgotOtp = url.build('lordajaxloginsign/ajax/verifyForgotOtp');
    const lordAjaxForgotSubmit = url.build('lordajaxloginsign/ajax/forgotSubmit');

    //define global self variable
    var self;

    function setCookie(name,value,days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days*24*60*60*1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "")  + expires + "; path=/";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }

    $(document).on("click",".toggle-password",function() {
        var input = $($(this).attr("toggle"));
        var thidButton = $(this);
        var datasecond = thidButton.attr("datasecond");
        var src = thidButton.attr("src");
        if (input.attr("type") == "password") {
            input.attr("type", "text");
            thidButton.attr("src", datasecond);
            thidButton.attr("datasecond", src);
        } else {
            input.attr("type", "password");
            thidButton.attr("src", datasecond);
            thidButton.attr("datasecond", src);
        }
    });

    // When the user clicks anywhere outside of the modal, close it
    function addClickListeners(event) {
        var getCurrentUrl = window.location.href;
        if (getCurrentUrl.includes("customer/account/login") == false && getCurrentUrl.includes("customer/account/create") == false && getCurrentUrl.includes("customer/account/forgotpassword") == false) {
            var lordhairModal = document.querySelector(".lordhairModal");
            if (event.target == lordhairModal) {
                if (self.isLoggedIn()) {
                    setCookie('closeVerifyPopup',1,1);
                }
                self.isLoginSignupShow(false);
                $(".quick-login").parents('.right-column').removeAttr('style');
            }
            var iti__selected_flag = document.querySelector(".iti__selected-flag");
            var iti__flag = document.querySelector(".iti__flag");
            if (event.target != iti__selected_flag && event.target != iti__flag) {
                self.isOpenCountryList(false);
            }
        }
    }
    window.addEventListener('click', addClickListeners);

    //Check Userlogged in or not
    var parseInputAttributes = true;
    var isLordCustomerLoggedIn = false;
    var getLordCustomerInfo = {};
    var varStoreConfigs = {};

    $.ajax({
        type: "POST",
        url: lordAjaxCheckLoggedIn,
        async: false,
        data: {
            'checkLoggedIn' : true,
        },
        dataType: "json",
        success: function(response) {
            if (!response.errors) {
                parseInputAttributes = false;
                isLordCustomerLoggedIn = true;
                getLordCustomerInfo = response.customer;
            }
            if (response.storeConfigs) {
                varStoreConfigs = response.storeConfigs;
            }
        },
        error: function(xhr, status, error){
            if (status === 0) {
                alert($t('Not connect.n Verify Network.'));
            } else if (status == 404) {
                alert($t('Requested page not found. [404]'));
            } else if (status == 500) {
                alert('Internal Server Error [500].');
            } else if (exception === 'parsererror') {
                alert('Requested JSON parse failed.');
            } else if (exception === 'timeout') {
                alert('Time out error.');
            } else if (exception === 'abort') {
                alert('Ajax request aborted.');
            } else {
                alert('Uncaught Error.n' + xhr.statusText);
            }
        }
    });

    ko.validation.init({
        registerExtenders: true,
        messagesOnModified: true,
        insertMessages: false,
        parseInputAttributes: parseInputAttributes,
        messageTemplate: null,
        errorElementClass: 'input-error',
        errorClass: 'message-error',
        decorateElementOnModified: true,
        decorateInputElement: true
    }, true);

    ko.bindingHandlers.trimedValue = {
        init: function (element, valueAccessor, allBindingsAccessor) {
            $(element).on("change", function () {
                var observable = valueAccessor();
                var trimedValue = $.trim($(this).val());
                observable($(this).val());
                observable(trimedValue);
            });
        },
        update: function (element, valueAccessor) {
            var value = ko.utils.unwrapObservable(valueAccessor());
            var trimedValue = $.trim(value);
            $(element).val(trimedValue);
        }
    };

    ko.bindingHandlers.maxLength = {
        init: function(element, valueAccessor) {
            function excludeFromLength(key) {
                var specialCharacters = [8,46,37,38,39,40];
                return ( specialCharacters.indexOf(key) !== -1 )
            }
            var maxLength = Number($(element).attr("maxLength"));
            $(element).on("keydown", function(e) {
                var key = e.which;
                if (excludeFromLength(key)) {
                    return true;
                }
                return Number($(this).val().length) < maxLength
            });
        }
    };
    ko.bindingHandlers.numeric = {
        init: function(element, valueAccessor, allBindings, viewModel,bidingContext) {
            $(element).attr('type', 'number');
            var maxLength = $(element).attr("maxLength");
            if (maxLength)  {
                ko.bindingHandlers.maxLength.init(element, valueAccessor);
            }
        }
    };
    ko.bindingHandlers.enterkey = {
        init: function (element, valueAccessor, allBindings, viewModel) {
            var callback = valueAccessor();
            $(element).keypress(function (event) {
                var keyCode = (event.which ? event.which : event.keyCode);
                if (keyCode === 13) {
                    callback.call(viewModel);
                    return false;
                }
                return true;
            });
        }
    };

    return Component.extend({
        loginCheckEmailOrPass: ko.observable(false),
        existingUserPhone: ko.observable(false),
        getStoreConfig: ko.observable(''),
        loginEmailMobile: ko.observable(''),
        loginEmailMobileCheck: ko.observable(''),
        loginEmailExist: ko.observable(''),
        loginPassword: ko.observable(''),
        loginInvalid: ko.observable(''),
        phoneEntered: ko.observable(false),
        emailExist: ko.observable(false),
        phoneExist: ko.observable(false),
        loginFBStat: ko.observable(true),
        accountnotExistMessage: ko.observable($t('Account not exist with entered details.')),
        loginInvalidMessage: ko.observable($t('Invalid login or password.')),
        otpSent: ko.observable(false),
        otpSuccessMessage: ko.observable(''),
        OtpInput1: ko.observable(''),
        OtpInput2: ko.observable(''),
        OtpInput3: ko.observable(''),
        OtpInput4: ko.observable(''),
        isLoggedIn: ko.observable(false),
        customerData: ko.observable({}),
        allErrors: ko.observable(''),
        showCommonErrors: ko.observable(''),
        countryCodeData: ko.observableArray([]),
        isInitLoading: ko.observable(true),
        isLoading: ko.observable(false),
        isLoginSignupShow: ko.observable(true),
        currentTab: ko.observable(''),
        selectedTab: ko.observable(null),
        init: ko.observable(1),
        registerCurrentScreen: ko.observable(''),
        loginCurrentScreen: ko.observable('login'),
        tabListVisible: ko.observable(true),
        isOpenCountryList: ko.observable(false),
        regEmail: ko.observable(''),
        regFirstName : ko.observable(''),
        regLastName : ko.observable(''),
        regCountryCode : ko.observable(''),
        selectedCountryCode : ko.observable({}),
        regMobileNo : ko.observable(''),
        regGender : ko.observable('1080'),
        regPasssword : ko.observable(''),
        regConfPasssword : ko.observable(''),
        regPasswordsMatch : ko.observable(false),
        regSignNewsletter : ko.observable(true),
        regSuccessMessage: ko.observable(''),
        regShowErrorContry: ko.observable(''),
        regPhoneOtpShow: ko.observable(false),
        regPhoneVerify : ko.observable(false),
        isConfirmRequired : ko.observable(false),
        forgotScreenStep : ko.observable('forgotEmail'),
        defaults: {
            template: 'Lordhair_LoginSignup/form',
        },
        initialize: function () {
            self = this;
            self._super();
            self.isLoading(true);
            self.currentTab('tab-1');
            self.loginEmailMobile.extend({
                required: true
            });
            self.loginEmailMobileCheck.extend({
                required: {
                    onlyIf: function() {
                        return self.loginCheckEmailOrPass() === true && self.loginEmailMobile();
                    },
                    message: $t("Please enter valid Mobile or Email!"),
                }
            });
            self.loginEmailExist.extend({
                required: {
                    onlyIf: function() {
                        return self.emailExist() === true;
                    },
                    message: function() {
                        return self.accountnotExistMessage()
                    }
                }
            });
            self.loginPassword.extend({
                required: {
                    onlyIf: function() {
                        return self.loginCheckEmailOrPass() === false && self.loginEmailMobile();
                    },
                    message: "Please enter password!",
                }
            });
            self.loginInvalid.extend({
                required: {
                    onlyIf: function() {
                        return self.loginPassword() && self.emailExist() === true;
                    },
                    message: function() {
                        return self.loginInvalidMessage()
                    }
                }
            });

            //Login Fist Step
            self.loginStep1errors = ko.validation.group({
                loginEmailMobile: self.loginEmailMobile,
                loginEmailMobileCheck: self.loginEmailMobileCheck
            });
            self.isValidLoginStep1 = ko.computed(function () {
                return self.loginStep1errors().length === 0;
            }, self);
            self.showAllMessagesStep1 = ko.computed(function () {
                return self.loginStep1errors.showAllMessages();
            }, self, {deferEvaluation : true});

            //Login Second Step
            self.loginStep2errors = ko.validation.group({
                loginEmailExist: self.loginEmailExist
            });
            self.isValidLoginStep2 = ko.computed(function () {
                return self.loginStep2errors().length === 0;
            }, self);
            self.showAllMessagesStep2 = ko.computed(function () {
                return self.loginStep2errors.showAllMessages();
            }, self, {deferEvaluation : true});

            //Login Second Step
            self.loginStep3errors = ko.validation.group({
                loginPassword: self.loginPassword
            });
            self.isValidLoginStep3 = ko.computed(function () {
                return self.loginStep3errors().length === 0;
            }, self);
            self.showAllMessagesStep3 = ko.computed(function () {
                return self.loginStep3errors.showAllMessages();
            }, self, {deferEvaluation : true});

            //Login Invalid Error
            self.loginStep4errors = ko.validation.group({
                loginInvalid: self.loginInvalid
            });
            self.isValidLoginStep4 = ko.computed(function () {
                return self.loginStep4errors().length === 0;
            }, self);
            self.showAllMessagesStep4 = ko.computed(function () {
                return self.loginStep4errors.showAllMessages();
            }, self, {deferEvaluation : true});

            //Login OTP Step
            self.loginOtperrors = ko.validation.group(self.loginEmailMobile);
            self.isValid = ko.computed(function () {
                return self.loginOtperrors().length === 0;
            }, self);
            self.showAllMessagesOtpStep = ko.computed(function () {
                return self.errors.showAllMessages();
            }, self, {deferEvaluation : true});

            //Forgot Password Email Step
            self.forgotStep1errors = ko.validation.group({
                loginEmailMobile: self.loginEmailMobile
            });
            self.isValidForgotStep1 = ko.computed(function () {
                return self.forgotStep1errors().length === 0;
            }, self);
            self.showAllMessagesForgotStep1 = ko.computed(function () {
                return self.forgotStep1errors.showAllMessages();
            }, self, {deferEvaluation : true});

            //Forgot Password Submit Step
            self.forgotSubmiterrors = ko.validation.group({
                regPasssword: self.regPasssword,
                regConfPasssword: self.regConfPasssword,
            });
            self.isValidForgotSubmit = ko.computed(function () {
                return self.forgotSubmiterrors().length === 0;
            }, self);
            self.showAllMessagesForgotSubmit = ko.computed(function () {
                return self.forgotSubmiterrors.showAllMessages();
            }, self, {deferEvaluation : true});

            //register process

            self.regFirstName.extend({
                required: true,
                validation: [
                    {
                        validator: function(val) {
                            var nameRegex = /(http|https|\.|:)/;
                            return !val || !nameRegex.test(val);
                        },
                        message: "Please enter valid Characters"
                    },
                    {
                    validator: function(val){
                        return Number(val.length) <= 25;
                    },
                    message:"Max 25 characters."
                }]
            });
            self.regLastName.extend({
                required: true,
                validation: [
                    {
                        validator: function(val) {
                            var nameRegex = /(http|https|\.|:)/;
                            return !val || !nameRegex.test(val);
                        },
                        message: "Please enter valid Characters"
                    },
                    {
                    validator: function(val){
                        return Number(val.length) <= 25;
                    },
                    message:"Max 25 characters."
                }]
            });
            self.regEmail.extend({
                required: true,
                email: true,
                validation: [
                    {
                        validator: function() {
                            return !self.emailExist()
                        },
                        message: function() {
                            return self.loginInvalidMessage()
                        }
                    }
                ]
            });
            self.regPasssword.extend({
                required: true,
                validation: [
                    {
                        validator: function(val) {
                            return Number(val.length) >= 6;
                        },
                        message: "Needs at least 6 characters."
                    }
                ]
            });
            self.regMobileNo.extend(
                {
                    validation: [
                        {
                            validator: function(val) {
                                var phoneRegex = /^([0|\+[0-9]{1,5})?([0-9]{9,15})$/;
                                return !val || phoneRegex.test(val);
                            },
                            message: "Please enter valid Mobile!"
                        },
                        {
                            validator: function() {
                                return !self.phoneExist()
                            },
                            message: function() {
                                return self.loginInvalidMessage()
                            }
                        }
                    ]
                }
            );
            self.regCountryCode.extend(
                {
                    required: {
                        onlyIf: function() {
                            return self.regMobileNo();
                        },
                        message: "Choose Country",
                    }
                }
            );
            self.regConfPasssword.extend(
                {
                    validation: [
                        {
                            validator: function() {
                                return self.regPasssword();
                            },
                            message: "This field is required."
                        },
                        {
                            validator: function(val) {
                                return val === self.regPasssword();
                            },
                            message: "Passwords must match"
                        }
                    ]
                }
            );

            //validation steps for registration
            self.regStep1errors = ko.validation.group({
                regFirstName: self.regFirstName,
                regLastName: self.regLastName,
                regMobileNo: self.regMobileNo,
                regCountryCode: self.regCountryCode,
                regEmail: self.regEmail,
                regPasssword: self.regPasssword,
                regConfPasssword: self.regConfPasssword,
            });
            self.isValidRegStep1 = ko.computed(function () {
                return self.regStep1errors().length === 0;
            }, self);
            self.showAllMessagesRegStep1 = ko.computed(function () {
                return self.regStep1errors.showAllMessages();
            }, self, {deferEvaluation : true});

            //validation steps for mobile otp
            self.mobileOtperrors = ko.validation.group({
                regMobileNo: self.regMobileNo,
                regCountryCode: self.regCountryCode,
            });
            self.isValidMobileOtp = ko.computed(function () {
                return self.mobileOtperrors().length === 0;
            }, self);
            self.showAllMessagesMobileOtp = ko.computed(function () {
                return self.mobileOtperrors.showAllMessages();
            }, self, {deferEvaluation : true});
            self._render();
        },
        _render:function(){
            if (isLordCustomerLoggedIn) {
                self.isLoggedIn(true);
                self.customerData(getLordCustomerInfo);
                if (!getLordCustomerInfo.telephone || getLordCustomerInfo.mobileConfirmation == 0) {
                    var closeVerifyPopup = getCookie('closeVerifyPopup');
                    if (!closeVerifyPopup) {
                        self.getCountryCode(self);
                        self.geoIpLookup();
                        self.existingUserPhone(true);
                        self.tabListVisible(false);
                        self.selectedTab('tab-2');
                        self.init(2);
                        self.regMobileNo(getLordCustomerInfo.telephone);
                        self.registerCurrentScreen('existingUserPhone');
                        $(".loginSignupContainer").show();
                        $(".loginSignupContainer").addClass('modal-animation');
                        self.isLoginSignupShow(false);
                    }
                }
            }
            self.getStoreConfig(varStoreConfigs);
            if (window.location.href.indexOf(`customer/account/create`) !== -1) {
                self.selectedTab('tab-2');
                self.registerCurrentScreen('register');
                self.init(2);
            }
            if (window.location.href.indexOf(`customer/account/login`) !== -1) {
                self.selectedTab('tab-1');
                self.init(1);
            }
            self.isLoading(false);
            self.isInitLoading(false);
            self.showLoginMenu();
        },
        showLoginMenu: function () {
            jQuery('.header-top .quick-login').removeClass("notLoading");
        },
        koLogout: function () {
            $.ajax({
                type: "GET",
                url: lordAjaxLogout,
                async: false,
                dataType: "json",
                success: function(response) {
                    if (!response.errors) {
                        self.loginCurrentScreen('login');
                        self.isLoggedIn(false);
                        window.location.reload();
                        return false;
                    } else {
                        return true;
                    }
                }
            });
        },
        getCountryCode: function (self) {
            $.ajax({
                type: 'GET',
                url: `${gePubMediaLink}/countryCode.json`,
                dataType: "json",
                success: function(data) {
                    self.countryCodeData(data);
                },
                error:function(jq, st, error){
                    console.log(error);
                }
            });
        },
        geoIpLookup: async function() {
            self.regCountryCode('+1');
                self.selectedCountryCode({
                    "name":"United States",
                    "dial_code":"+1",
                    "className":"iti__us",
                    "code":"US"
                });
        },
        openCountryList: function () {
            self.isOpenCountryList(true);
        },
        selectCountryFun: function (data,event) {
            self.isOpenCountryList(false);
            self.selectedCountryCode(data);
            self.regCountryCode(data.dial_code);
        },
        popupCloseButton: function () {
            var getCurrentUrl = window.location.href;
            if (getCurrentUrl.includes("customer/account/login") || getCurrentUrl.includes("customer/account/create") || getCurrentUrl.includes("customer/account/forgotpassword")) {
                window.location = getSiteUrl;
            }else{
                if (self.isLoggedIn()) {
                    setCookie('closeVerifyPopup',1,1);
                }
                self.isLoginSignupShow(false);
                $(".quick-login").parents('.right-column').removeAttr('style');
            }
        },
        showPopupButton: function () {
            self.isLoginSignupShow(true);
        },
        getHref: function(event){
            var currentTab = event.target.getAttribute('data-tab');
            return currentTab;
        },
        changeTab: function (data, event) {
            var target = self.getHref(event);
            self.getCountryCode(self);
            self.geoIpLookup();
            self.selectedTab(target);
            if (target == 'tab-1' && self.registerCurrentScreen() == 'register') {
                self.registerCurrentScreen('reset');
            } else {
                if (self.registerCurrentScreen() == '' || self.registerCurrentScreen() == 'reset') {
                    self.registerCurrentScreen('register');
                }
            }
            self.init(2);
        },
        checkCurrentTab: function (getCurrentTab) {
            if (self.currentTab() == getCurrentTab) {
                return true;
            }
            return false;
        },
        getSiteUrl: function (path) {
            if (path != '') {
                return `${getSiteUrl}${path}`;
            }
        },
        getImageUrl: function (imageName) {
            if (imageName != '') {
                return `${gePubMediaLink}/${imageName}`;
            }
        },
        getTopImage: function () {
            if (screen.width < 1024) {
                return self.getImageUrl(self.getStoreConfig().mobileImage);
            } else{
                return self.getImageUrl(self.getStoreConfig().desktopImage);
            }
        },
        validatePhone: function () {
            var phoneRegex = /^([0|\+[0-9]{1,5})?([0-9]{9,15})$/;
            if (phoneRegex.test(self.loginEmailMobile())) {
                return true;
            }
        },
        validateEmailPhone: function () {
            var mailFormat = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})|([0-9]{8})+$/;
            if (self.validatePhone()) {
                self.phoneEntered(true);
            }else{
                self.phoneEntered(false);
            }
            if (!self.phoneEntered() && !mailFormat.test(self.loginEmailMobile())) {
                return false;
            }
            return true;
        },
        checkEmailExist: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxLogin,
                async: false,
                data: {
                    'username' : self.loginEmailMobile(),
                    'checkEmailExist' : true,
                    'phoneEntered' : self.phoneEntered(),
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.emailExist(true);
                        self.accountnotExistMessage(response.message)
                    }
                }
            });
        },
        loginStep1: function() {
            self.otpSent(false);
            self.otpSuccessMessage('');

            const getEmailMob = $("#loginEmailMobile").val();
            self.loginEmailMobile(getEmailMob);

            // required or not second error condition
            if (self.loginEmailMobile() && !self.validateEmailPhone()) {
                self.loginCheckEmailOrPass(true);
            }else{
                self.loginCheckEmailOrPass(false);
            }

            // false to email exist first
            self.emailExist(false);

            // checking steps valid or not
            if (self.isValidLoginStep1()){
                self.isLoading(true);
                self.checkEmailExist();
            } else {
                self.showAllMessagesStep1();
                return false;
            }

            // checking steps for email exist or not
            if (self.emailExist() === false && self.isValidLoginStep2()) {
                self.loginCurrentScreen('loginPass');
            } else {
                self.showAllMessagesStep2();
                return false;
            }
        },
        login: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxLogin,
                async: false,
                data: {
                    'username' : self.loginEmailMobile(),
                    'password' : self.loginPassword(),
                    'phoneEntered' : self.phoneEntered(),
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.emailExist(true);
                        self.loginInvalidMessage(response.message);
                        if (response.errorType == 'emailNotConfirm') {
                            self.isConfirmRequired(true);
                        }
                    } else {
                        self.emailExist(false);
                        //save customer
                        self.customerData(response.customer);
                    }
                }
            });
        },
        loginWithPass: function() {

            const getLoginPassword = $("#password").val();
            self.loginPassword(getLoginPassword);

            if (self.isValidLoginStep3()){
                self.isLoading(true);
                self.login();
            } else {
                self.showAllMessagesStep3();
                return false;
            }
            if (self.isValidLoginStep4()){
                //close popup after login
                self.isLoggedIn(true);
                self.otpSuccessMessage($.mage.__('You are successfully logged in'));
                self.loginCurrentScreen('loginSuccess');
                self.closePopupConditions();

            } else {
                self.showAllMessagesStep4();
                return false;
            }
        },
        sendVerifyEmailOtp: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxResendEmailOtp,
                async: false,
                data: {
                    'email' : self.loginEmailMobile(),
                    'phoneEntered' : self.phoneEntered(),
                },
                dataType: "json",
                success: function(response) {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.otpSent(true);
                        self.otpSuccessMessage($t('OTP successfully sent to your email.Enter the One-time-password for verification .If you didn’t receive the code, resend it.'));
                    }
                }
            });
        },
        loginVerifyEmailAjax: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxVerifyEmailOtp,
                async: false,
                data: {
                    'email' : self.loginEmailMobile(),
                    'otp' : [
                        self.OtpInput1(),
                        self.OtpInput2(),
                        self.OtpInput3(),
                        self.OtpInput4()
                    ],
                },
                dataType: "json",
                success: function(response) {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.isLoggedIn(true);
                        self.isConfirmRequired(false);
                        self.customerData(response.customer);
                        self.otpSuccessMessage($.mage.__('You are successfully logged in'));
                        self.loginCurrentScreen('loginSuccess');
                        self.closePopupConditions();
                    }
                }
            });
        },
        closePopupConditions: function() {
            if (window.location.href.indexOf(`customer/account/login`) !== -1 || window.location.href.indexOf(`customer/account/create`) !== -1) {
                window.location = `${getSiteUrl}customer/account/`;
                $(".quick-login").parents('.right-column').removeAttr('style');
            }else{
                setTimeout(function(){
                    self.isLoginSignupShow(false);
                    $(".quick-login").parents('.right-column').removeAttr('style');
                    window.location.reload();
                }, 3000);
            }
        },
        verifyNowCall: function() {
            self.isLoading(true);
            self.sendVerifyEmailOtp();
            self.loginCurrentScreen('loginEmailOtp');
        },
        loginResendOtpEmail: function() {
            self.resetOtp();
            self.isLoading(true);
            self.sendVerifyEmailOtp();
        },
        loginVerifyEmailButton: function() {
            self.isLoading(true);
            self.loginVerifyEmailAjax();
        },
        sendOtp: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxSendOtp,
                async: false,
                data: {
                    'telephone' : self.loginEmailMobile(),
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.otpSent(true);
                        self.otpSuccessMessage(response.message);
                    }
                }
            });
        },
        loginStep2: function() {
            self.resetOtp();
            self.sendOtp();
            self.loginCurrentScreen('loginOtp');
        },
        resendOtp: function() {
            self.resetOtp();
            self.sendOtp();
        },
        resetOtp: function() {
            self.OtpInput1('');
            self.OtpInput2('');
            self.OtpInput3('');
            self.OtpInput4('');
        },
        loginAjaxWithOtp: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxLoginOtp,
                async: false,
                data: {
                    'username' : self.loginEmailMobile(),
                    'otp' : [
                        self.OtpInput1(),
                        self.OtpInput2(),
                        self.OtpInput3(),
                        self.OtpInput4()
                    ],
                    'phoneEntered' : self.phoneEntered(),
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.otpSent(true);
                        self.otpSuccessMessage(response.message);
                        //save customer
                        self.isLoggedIn(true);
                        self.customerData(response.customer);
                        //close popup after login
                        self.closePopupConditions();
                    }
                }
            });
        },
        loginWithOtp: function () {
            if (self.OtpInput1() && self.OtpInput2() && self.OtpInput3() && self.OtpInput4()) {
                self.loginAjaxWithOtp();
            } else{
                self.otpSuccessMessage('Please enter valid OTP!');
            }
        },
        otpValueUpdateLogin: function (data, event) {
            self.otpSuccessMessage('');
            if(event.keyCode === 8 || event.keyCode === 37) {
                $(event.currentTarget).prev('input').focus();
            } else {
                var maxLength = Number($(event.currentTarget).attr("maxLength"));
                if (Number($(event.currentTarget).val().length) >= maxLength) {
                    $(event.currentTarget).next('input').focus();
                }
            }
            if (self.OtpInput1() && self.OtpInput2() && self.OtpInput3() && self.OtpInput4()) {
                self.loginAjaxWithOtp();
            }
        },
        otpValueUpdate: function (data, event) {
            self.otpSuccessMessage('');
            if(event.keyCode === 8 || event.keyCode === 37) {
                $(event.currentTarget).prev('input').focus();
            } else {
                var maxLength = Number($(event.currentTarget).attr("maxLength"));
                if (Number($(event.currentTarget).val().length) >= maxLength) {
                    $(event.currentTarget).next('input').focus();
                }
            }
            if (self.OtpInput1() && self.OtpInput2() && self.OtpInput3() && self.OtpInput4()) {
                self.forgotVerifyOtp();
            }
        },
        forgotButton: function() {
            self.registerCurrentScreen('forgotPass');
            self.selectedTab('tab-2');
            self.init(2);
            self.isConfirmRequired(false);
            self.forgotScreenStep('forgotEmail');
            self.tabListVisible(false);
        },
        loginButton: function() {
            self.registerCurrentScreen('register');
            self.loginCurrentScreen('login');
            self.selectedTab('tab-1');
            self.otpSuccessMessage('');
            self.isConfirmRequired(false);
            self.tabListVisible(true);
        },
        //register Process Steps
        registerAjaxStep1: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxRegister,
                async: false,
                data: {
                    'regEmail' : self.regEmail(),
                    'regFirstName' : self.regFirstName(),
                    'regLastName' : self.regLastName(),
                    'regCountryCode' : self.regCountryCode(),
                    'regCountryISOCode' : self.selectedCountryCode().code,
                    'regMobileNo' : self.regMobileNo(),
                    'regGender' : self.regGender(),
                    'regPasssword' : self.regPasssword(),
                    'regConfPasssword' : self.regConfPasssword(),
                    'regSignNewsletter' : self.regSignNewsletter(),
                    'mobileconfirmation' : self.regPhoneVerify(),
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                success: function(response) {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                    if (response.errors) {
                        switch (response.errotTyep) {
                            case 'emialExist':
                                self.emailExist(true);
                                self.loginInvalidMessage(response.message);
                                break;
                            case 'phoneExist':
                                self.phoneExist(true);
                                self.loginInvalidMessage(response.message);
                                break;
                            case 'something':
                                self.showCommonErrors(response.message);
                                break;
                            default:
                                self.showCommonErrors(response.message);
                        }
                    } else {
                        self.registerCurrentScreen('registerOtp');
                        self.showCommonErrors('');
                        self.regSuccessMessage(response.message);
                    }
                }
            });
        },
        regEmailKeyUp: function() {
            self.loginInvalidMessage('');
        },
        registerStep1: function() {
            self.emailExist(false);
            self.phoneExist(false);
            self.loginInvalidMessage('');
            self.regShowErrorContry('');
            // checking steps valid or not
            if (self.isValidRegStep1()){
                self.isLoading(true);
                self.resetOtp();
                self.registerAjaxStep1();
            } else {
                self.showAllMessagesRegStep1();
                return false;
            }
        },
        regMobileUpdate: function() {
            self.otpSuccessMessage('');
            if (self.regPhoneVerify() === true) {
                self.regPhoneVerify(false);
                return true;
            }
        },
        checkPhoneExist: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxLogin,
                async: false,
                data: {
                    'username' : self.regMobileNo(),
                    'checkEmailExist' : true,
                    'phoneEntered' : self.phoneEntered(),
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                success: function(response) {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                    if (response.errors == false) {
                        self.phoneExist(true);
                        self.loginInvalidMessage(response.message);
                    } else {
                        if (self.existingUserPhone() === true) {
                            self.sendPostLogedOtp();
                        } else {
                            self.sendPostRegOtp();
                        }
                    }
                }
            });
        },
        sendPostRegOtp: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxSendOtp,
                async: false,
                data: {
                    'telephone' : self.regMobileNo(),
                    'registration' : true,
                    'countryCode' : self.regCountryCode()
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.otpSent(true);
                        self.otpSuccessMessage(response.message);
                        self.regPhoneOtpShow(true);
                    }
                }
            });
        },
        regVerifyPhoneButton: function() {
            self.resetOtp();
            self.phoneEntered(true);
            self.phoneExist(false);
            self.loginInvalidMessage('');
            if (!self.regCountryCode()) {
                self.regShowErrorContry('Choose country');
                return false;
            }
            if (!self.regMobileNo()) {
                return false;
            }
            if (self.isValidMobileOtp()){
                self.isLoading(true);
                self.regShowErrorContry('');
                self.checkPhoneExist();
            } else {
                self.showAllMessagesMobileOtp();
                return false;
            }
        },
        sendPostRegVerifyOtp: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxOtpVerify,
                async: false,
                data: {
                    'telephone' : self.regMobileNo(),
                    'countryCode' : self.regCountryCode(),
                    'otp' : [
                        self.OtpInput1(),
                        self.OtpInput2(),
                        self.OtpInput3(),
                        self.OtpInput4()
                    ],
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.regPhoneVerify(false);
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.regPhoneVerify(true);
                        self.regPhoneOtpShow(false);
                    }
                }
            });
        },
        otpRegValueUpdate: function (data, event) {
            self.otpSuccessMessage('');
            self.regSuccessMessage('');
            self.showCommonErrors('');
            if(event.keyCode === 8 || event.keyCode === 37) {
                $(event.currentTarget).prev('input').focus();
            } else {
                var maxLength = Number($(event.currentTarget).attr("maxLength"));
                if (Number($(event.currentTarget).val().length) >= maxLength) {
                    $(event.currentTarget).next('input').focus();
                }
            }
            if (self.OtpInput1() && self.OtpInput2() && self.OtpInput3() && self.OtpInput4()) {

                if (self.existingUserPhone() === true) {
                    self.sendPostLoginVerifyOtp();
                } else {
                    self.sendPostRegVerifyOtp();
                }
            }
        },
        otpVerifyValueUpdate: function (data, event) {
            self.regSuccessMessage('');
            self.showCommonErrors('');
            if(event.keyCode === 8 || event.keyCode === 37) {
                $(event.currentTarget).prev('input').focus();
            } else {
                var maxLength = Number($(event.currentTarget).attr("maxLength"));
                if (Number($(event.currentTarget).val().length) >= maxLength) {
                    $(event.currentTarget).next('input').focus();
                }
            }
            if (self.OtpInput1() && self.OtpInput2() && self.OtpInput3() && self.OtpInput4()) {
                self.regVerifyEmailButton();
            }
        },
        emailConfirmVerifyOtpUpdate: function (data, event) {
            self.regSuccessMessage('');
            self.showCommonErrors('');
            if(event.keyCode === 8 || event.keyCode === 37) {
                $(event.currentTarget).prev('input').focus();
            } else {
                var maxLength = Number($(event.currentTarget).attr("maxLength"));
                if (Number($(event.currentTarget).val().length) >= maxLength) {
                    $(event.currentTarget).next('input').focus();
                }
            }
            if (self.OtpInput1() && self.OtpInput2() && self.OtpInput3() && self.OtpInput4()) {
                self.loginVerifyEmailButton();
            }
        },
        regCloseOtpVerify: function() {
            self.regPhoneOtpShow(false);
            self.otpSuccessMessage('');
        },
        regResendOtpEmail: function() {
            self.resetOtp();
            $.ajax({
                type: "POST",
                url: lordAjaxResendEmailOtp,
                async: false,
                data: {
                    'email' : self.regEmail()
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.otpSent(true);
                        self.otpSuccessMessage(response.message);
                    }
                }
            });
        },
        regVerifyEmailButton: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxVerifyEmailOtp,
                async: false,
                data: {
                    'email' : self.regEmail(),
                    'otp' : [
                        self.OtpInput1(),
                        self.OtpInput2(),
                        self.OtpInput3(),
                        self.OtpInput4()
                    ],
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.isLoggedIn(true);
                        self.customerData(response.customer);
                        self.otpSuccessMessage($.mage.__('You are successfully logged in'));
                        self.registerCurrentScreen('loginSuccess');
                        self.closePopupConditions();
                    }
                }
            });
        },
        sendPostLoginVerifyOtp: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxOtpVerify,
                async: false,
                data: {
                    'telephone'  : self.regMobileNo(),
                    'countryCode' : self.regCountryCode(),
                    'isLoggedin' : true,
                    'email' : getLordCustomerInfo.email,
                    'otp' : [
                        self.OtpInput1(),
                        self.OtpInput2(),
                        self.OtpInput3(),
                        self.OtpInput4()
                    ],
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.regPhoneVerify(false);
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.regPhoneVerify(true);
                        self.otpSuccessMessage($.mage.__('Verified successfully.'));
                        self.registerCurrentScreen('loginSuccess');
                        self.closePopupConditions();
                    }
                }
            });
        },
        sendPostLogedOtp: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxSendOtp,
                async: false,
                data: {
                    'telephone' : self.regMobileNo(),
                    'email' : getLordCustomerInfo.email,
                    'countryCode' : self.regCountryCode()
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.otpSent(true);
                        self.otpSuccessMessage(response.message);
                        self.regPhoneOtpShow(true);
                    }
                }
            });
        },
        forgotOtpEmail: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxForgotEmailOtp,
                async: false,
                data: {
                    'email' : self.loginEmailMobile()
                },
                dataType: "json",
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.otpSent(true);
                        self.otpSuccessMessage(response.message);
                        self.forgotScreenStep('forgotOtp');
                    }
                }
            });
        },
        forgtVerifyOTP: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxVerifyForgotOtp,
                async: false,
                data: {
                    'email' : self.loginEmailMobile(),
                    'otp' : [
                        self.OtpInput1(),
                        self.OtpInput2(),
                        self.OtpInput3(),
                        self.OtpInput4()
                    ],
                },
                dataType: "json",
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.otpSent(true);
                        self.otpSuccessMessage(response.message);
                        self.forgotScreenStep('forgtPassword');
                    }
                }
            });
        },
        resetToLogin: function() {
            self.forgotScreenStep('forgtPassword');
            self.registerCurrentScreen('register');
            self.loginCurrentScreen('login');
            self.selectedTab('tab-1');
            self.isConfirmRequired(false);
            self.tabListVisible(true);
        },
        forgotSubmitCall: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxForgotSubmit,
                async: false,
                data: {
                    'email' : self.loginEmailMobile(),
                    'passsword' : self.regPasssword(),
                    'confPasssword' : self.regConfPasssword(),
                },
                dataType: "json",
                complete: function() {
                    setTimeout(function(){
                        self.isLoading(false);
                    }, 1000);
                },
                success: function(response) {
                    if (response.errors) {
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.otpSent(true);
                        self.otpSuccessMessage(response.message);
                        self.forgotScreenStep('forgotEmail');
                        self.resetToLogin();
                    }
                }
            });
        },
        forgotEmailClick: function() {
            if (self.isValidForgotStep1()){
                self.isLoading(true);
                self.forgotOtpEmail();
                self.resetOtp();
            } else {
                self.showAllMessagesForgotStep1();
                return false;
            }
        },
        forgotVerifyOtp: function() {
            if (self.OtpInput1() && self.OtpInput2() && self.OtpInput3() && self.OtpInput4()) {
                self.isLoading(true);
                self.forgtVerifyOTP();
            } else{
                self.otpSent(false);
                self.otpSuccessMessage('Please enter valid OTP!');
            }
        },
        forgotSubmit: function() {
            if (self.isValidForgotSubmit()){
                self.isLoading(true);
                self.forgotSubmitCall();
            } else {
                self.showAllMessagesForgotSubmit();
                return false;
            }
        }
    });
});
