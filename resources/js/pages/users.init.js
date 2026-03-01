/*
Template Name: Skote - Admin & Dashboard Template
Author: Themesbrand
Website: https://themesbrand.com/
Contact: themesbrand@gmail.com
File: project create Init Js File
*/

// Form Event
(function () {
    'use strict'

    $('#duedate-input').datepicker({
        language: 'es', // Configura el idioma a español
    });
    // Establecer la fecha del datepicker usando el valor del input (yyyy-mm-dd)
    var dob = $('#duedate-input').val();  // Esto obtiene el valor en yyyy-mm-dd

    if (dob) {
        var dobDate = new Date(dob);  // "dob" es un string en formato yyyy-mm-dd
        // Configura el datepicker con la fecha
        $('#duedate-input').datepicker('setDate', dobDate);  // Le pasamos la fecha en formato date
    }
    // project logo image
    document.querySelector("#project-image-input").addEventListener("change", function () {
        var preview = document.querySelector("#projectlogo-img");
        var file = document.querySelector("#project-image-input").files[0];
        var reader = new FileReader();
        reader.addEventListener("load", function () {
            preview.src = reader.result;
        }, false);
        if (file) {
            reader.readAsDataURL(file);
        }
    });


    

    
})()