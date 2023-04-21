define([
    'jquery',
    'uiComponent',
    'ko',
    'mage/url',
    'mage/translate',
    'underscore'
], function($, Component, ko, url, $t, _) {
    "use strict";
    url.setBaseUrl(BASE_URL);
    const gePubMediaLink = url.build('media/login-lignup');
    const lordAjaxCheckLoggedIn = url.build('lordajaxloginsign/ajax/checkloggedin');
    const lordAjaxOtpVerify = url.build('lordajaxloginsign/ajax/otpverify');
    const lordAjaxLogin = url.build('lordajaxloginsign/ajax/login');
    const lordAjaxSendOtp = url.build('lordajaxloginsign/ajax/sendotp');
    //define global self variable
    var self;
    var varStoreCustomer = {};
    var isLordCustomerLoggedIn = true;

    // When the user clicks anywhere outside of the modal, close it
    function addClickListeners(event) {
        var iti__selected_flag = document.querySelector(".profile__selected-flag");
        var iti__flag = document.querySelector(".activeFlag");
        var iti__arrow = document.querySelector(".profileArraowFlag");
        if (event.target != iti__selected_flag && event.target != iti__flag && event.target != iti__arrow) {
            self.isOpenCountryList(false);
        }
    }
    window.addEventListener('click', addClickListeners);

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
                isLordCustomerLoggedIn = true;
            }
            if (response.customer) {
                varStoreCustomer = response.customer;
            }
        },
        error: function(xhr, status, error){
            if (status === 0) {
                alert('Not connect.n Verify Network.');
            } else if (status == 404) {
                alert('Requested page not found. [404]');
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
        isLoading: ko.observable(false),
        phoneEntered: ko.observable(false),
        phoneExist: ko.observable(false),
        countryCodeData: ko.observableArray([]),
        otpSent: ko.observable(false),
        otpSuccessMessage: ko.observable(''),
        OtpInput1: ko.observable(''),
        OtpInput2: ko.observable(''),
        OtpInput3: ko.observable(''),
        OtpInput4: ko.observable(''),
        allErrors: ko.observable(''),
        regMobileNo: ko.observable(''),
        visibleVerifyBtn: ko.observable(false),
        regPhoneOtpShow: ko.observable(false),
        regPhoneVerify: ko.observable(false),
        getMobileConfirmation: ko.observable(false),
        isOpenCountryList: ko.observable(false),
        showCommonErrors: ko.observable(''),
        regCountryCode : ko.observable(''),
        regCountryISOCode: ko.observable(''),
        selectedCountryCode : ko.observable({
            "name":"",
            "dial_code":"",
            "className":"",
            "code":""
        }),
        initialize: function () {
            self = this;
            self._super();
            self._render();
        },
        _render:function(){
            self.getCountryCode();
            if (isLordCustomerLoggedIn) {
                self.regMobileNo(varStoreCustomer.telephone);
                if (varStoreCustomer.mobileConfirmation) {
                    self.getMobileConfirmation(varStoreCustomer.mobileConfirmation);
                }
            }
        },
        getCountryCode: function () {
            $.ajax({
                type: 'GET',
                url: `${gePubMediaLink}/countryCode.json`,
                dataType: "json",
                success: function(data) {
                    self.countryCodeData(data);
                    self.checkCurrentCountry();
                },
                error:function(jq, st, error){
                    console.log(error);
                }
            });
        },
        geoIpLookup: function() {
            self.regCountryCode('+1');
            self.selectedCountryCode({
                "name":"United States",
                "dial_code":"+1",
                "className":"iti__us",
                "code":"US"
            });
        },
        checkCurrentCountry: function () {
            if (varStoreCustomer && varStoreCustomer.countryCode) {
                var returnContry = [];
                if (varStoreCustomer.regCountryISOCode) {
                    returnContry = _.filter(self.countryCodeData(),function(country){
                        return country.code == varStoreCustomer.regCountryISOCode
                    });
                } else{
                    returnContry = _.filter(self.countryCodeData(),function(country){
                        return country.dial_code == varStoreCustomer.countryCode
                    });
                }
                if (returnContry && returnContry.length > 0) {
                    self.regCountryCode(returnContry[0].dial_code);
                    self.regCountryISOCode(returnContry[0].code);
                    self.selectedCountryCode(returnContry[0]);
                }
            } else { 
                self.geoIpLookup();
            }
        },
        openCountryList: function () {
            self.isOpenCountryList(true);
        },
        selectCountryFun: function (data,event) {
            self.isOpenCountryList(false);
            self.selectedCountryCode(data);
            self.regCountryCode(data.dial_code);
            self.regCountryISOCode(data.code);
        },
        regCloseOtpVerify: function() {
            self.regPhoneOtpShow(false);
            self.otpSuccessMessage('');
        },
        resetOtp: function() {
            self.OtpInput1('');
            self.OtpInput2('');
            self.OtpInput3('');
            self.OtpInput4('');
        },
        getImageUrl: function (imageName) {
            if (imageName != '') {
                return `${gePubMediaLink}/${imageName}`;
            }
        },
        regMobileUpdate: function() {
            self.visibleVerifyBtn(true);
            self.allErrors('');
            if (self.regPhoneVerify() === true) {
                self.regPhoneVerify(false);
                return true;
            }
        },
        sendPostRegOtp: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxSendOtp,
                async: false,
                data: {
                    'telephone' : self.regMobileNo(),
                    'email' : varStoreCustomer.email,
                    'countryCode' : self.regCountryCode()
                },
                dataType: "json",
                beforeSend: function () {
                    self.isLoading(true);
                },
                complete: function() {
                    self.isLoading(false);
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
                complete: function() {
                    self.isLoading(false);
                },
                success: function(response) {
                    if (response.errors == false) {
                        self.phoneExist(true);
                        self.allErrors(response.message);
                    } else {
                        self.allErrors('');
                        self.sendPostRegOtp();
                    }
                }
            });
        },
        regVerifyPhoneButton: function() {
            self.resetOtp();
            self.allErrors('');
            self.phoneEntered(true);
            self.phoneExist(false);
            if (!self.regMobileNo()) {
                return false;
            }
            var phoneRegex = /^([0|\+[0-9]{1,5})?([0-9]{9,15})$/;
            if (!phoneRegex.test(self.regMobileNo())) {
                self.allErrors('Enter Valid Mobile Number');
                return false;
            }
            self.checkPhoneExist();
        },
        sendPostRegVerifyOtp: function() {
            $.ajax({
                type: "POST",
                url: lordAjaxOtpVerify,
                async: false,
                data: {
                    'telephone'  : self.regMobileNo(),
                    'countryCode' : self.regCountryCode(),
                    'isLoggedin' : true,
                    'email' : varStoreCustomer.email,
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
                    self.isLoading(false);
                },
                success: function(response) {
                    if (response.errors) {
                        self.regPhoneVerify(false);
                        self.otpSent(false);
                        self.otpSuccessMessage(response.message);
                    } else {
                        self.regPhoneVerify(true);
                        self.regPhoneOtpShow(false);
                        self.otpSent(true);
                        self.otpSuccessMessage($.mage.__('Verified successfully.'));
                        setTimeout(function(){
                            self.otpSuccessMessage('');
                            self.visibleVerifyBtn(false);
                        }, 5000);
                    }
                }
            });
        },
        otpRegValueUpdate: function (data, event) {
            self.otpSuccessMessage('');
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
                self.sendPostRegVerifyOtp();
            }
        },
    });
});