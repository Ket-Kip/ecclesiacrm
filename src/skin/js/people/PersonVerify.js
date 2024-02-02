/*******************************************************************************
 *
 *  filename    : PersonVerify.js
 *  website     : http://www.ecclesiacrm.com
 *  copyright   : Copyright 2024 Philippe Logel
 *
 ******************************************************************************/

$(function() {
    $('#onlineVerifySiteBtn').hide();
    $("#confirm-modal-done").hide();
    $("#confirm-modal-error").hide();

    $("#onlineVerifyBtn").on('click', function () {

        $.post(window.CRM.root + '/ident/my-profile/onlineVerificationFinished/', {
            "token": window.CRM.token,
            "message" : $("#confirm-info-data").val()
        }, function (data) {
            $('#confirm-modal-collect').hide();
            $("#onlineVerifyCancelBtn").hide();
            $("#onlineVerifyBtn").hide();
            $("#onlineVerifySiteBtn").show();
            if (data.Status == "success") {
                $("#confirm-modal-done").show();
            } else {
                $("#confirm-modal-error").show();
            }
        });
    });

    function BootboxContent(data, custom) {

        var frm_str = '<form id="some-form">'
                + '  <div class="row">'
                + '     <div class="col-md-6">';
                            frm_str += data;
        frm_str +=  '  </div>'
                + '    <div class="col-md-6">';
                            frm_str += custom;
        frm_str += '    </div>'
                + '  </div>'
                + '</form>';

        var object = $('<div/>').html(frm_str).contents();

        return object;
    }

    function PersonWindow(data, custom, fields, personId) {

        var _fields = fields;

        var modal = bootbox.dialog({
            message: BootboxContent(data, custom),
            size: 'xl',
            buttons: [
                {
                    label: '<i class="fas fa-times"></i> ' + i18next.t("Close"),
                    className: "btn btn-default",
                    callback: function () {
                    }
                },
                {
                    label: '<i class="fas fa-check"></i> ' + i18next.t("Save"),
                    className: "btn btn-primary",
                    callback: function () {
                        var fields = _fields;
                        var Title = $('form #Title').val();
                        var FirstName = $('form #FirstName').val();
                        var MiddleName = $('form #MiddleName').val();
                        var LastName = $('form #LastName').val();
                        var Address1 = $('form #Address1').val();
                        var Address2 = $('form #Address2').val();
                        var Zip = $('form #Zip').val();
                        var City = $('form #City').val();
                        var FamilyRole = $( "#FamilyRole option:selected" ).val();
                        var PersonRole = $('form #PersonRole').val();
                        var homePhone = $('form #homePhone').val();
                        var workPhone = $('form #workPhone').val();
                        var cellPhone = $('form #cellPhone').val();
                        var email = $('form #email').val();
                        var workemail = $('form #workemail').val();
                        var BirthDayDate = $('form #BirthDayDate').val();
                        var WeddingDate = $('form #WeddingDate').val();
                        var SendNewsLetter = $('form #SendNewsLetter').is(':checked');
                        
                        var res_fields = new Object();
                        for (i=0;i<fields.length;i++) {
                            val = $( "." + fields[i] ).val();
                            res_fields[fields[i]] = val;                                            
                        }

                        var fmt = window.CRM.datePickerformat.toUpperCase();;

                        var real_BirthDayDate = moment(BirthDayDate,fmt).format('YYYY-MM-DD');

                        var real_WeddingDate = moment(WeddingDate,fmt).format('YYYY-MM-DD');

                        fetch(window.CRM.root + '/ident/my-profile/modifyPersonInfo/', {
                            method: 'POST',
                            headers: {
                                'Content-Type': "application/json; charset=utf-8",
                            },
                            body: JSON.stringify({
                                "token": window.CRM.token,
                                "personId": personId,
                                "Title": Title,
                                "FirstName": FirstName,
                                "MiddleName": MiddleName,
                                "LastName": LastName,
                                "PersonRole": PersonRole,
                                "homePhone": homePhone,
                                "workPhone": workPhone,
                                "cellPhone": cellPhone,
                                "email": email,
                                "workemail": workemail,
                                "BirthDayDate": real_BirthDayDate,
                                "WeddingDate": real_WeddingDate,
                                "type": "person",
                                "FamilyRole": FamilyRole,
                                "Address1": Address1,
                                "Address2": Address2,
                                "Zip": Zip,
                                "personFields": res_fields,
                                "City": City,
                                "SendNewsLetter": SendNewsLetter
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            // enter you logic when the fetch is successful
                            $(".person-container-" + personId).html(data.content);
                            $(".person-container-custom-" + personId).html(data.contentCustom);
                        })
                        .catch(error => {
                            // enter your logic for when there is an error (ex. error toast)
                            console.log(error)
                        });

                    }
                }
            ],
            show: false,
            onEscape: function () {
            }
        });

        // this will ensure that image and table can be focused
        $(document).on('focusin', function (e) {
            e.stopImmediatePropagation();
        });

        return modal;
    }

    $(document).on("click", ".modifyPerson", function () {
        var personId = $(this).data("id");

        $.post(window.CRM.root + '/ident/my-profile/getPersonInfo/', {"token": window.CRM.token, "personId": personId}, function (data) {
            var modal = PersonWindow(data.html, data.htmlCustom, data.fields, personId);
            modal.modal("show");

            $('.date-picker').datepicker({format: window.CRM.datePickerformat, language: window.CRM.lang});
        });
    });

    $(document).on("click", ".deletePerson", function () {
        var personId = $(this).data("id");

        bootbox.confirm(i18next.t("Confirm Delete"), function(confirmed) {
            if (confirmed) {
                $.post(window.CRM.root + '/ident/my-profile/deletePerson/', {
                    "token": window.CRM.token,
                    "personId": personId
                }, function (data) {
                    location.reload();
                });
            }
        });
    });

    $(document).on("click", ".exitSession", function () {
        $.post(window.CRM.root + '/ident/my-profile/exitSession/', {"token": window.CRM.token}, function (data) {
            window.location = window.location.href;
        });
    });
});
