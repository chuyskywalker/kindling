/**
 * Recover Forms
 * Forms must have an ID, they are only recovered if the HREF matches, and only form elements with name attributes will be saved.
 */

// Utility
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (elt /*, from*/) {
        var len = this.length >>> 0;
        var from = Number(arguments[1]) || 0;
        from = (from < 0) ? Math.ceil(from) : Math.floor(from);
        if (from < 0) {
            from += len;
        }
        for (; from < len; from++) {
            if (from in this && this[from] === elt) {
                return from;
            }
        }
        return -1;
    };
}

// http://stackoverflow.com/questions/2010892/storing-objects-in-html5-localstorage
Storage.prototype.setObject = function(key, value) {
    this.setItem(key, JSON.stringify(value));
};

Storage.prototype.getObject = function(key) {
    var value = this.getItem(key);
    return value && JSON.parse(value);
};

// http://www.kirupa.com/html5/html5_local_storage.htm
function isLocalStorageSupported() {
    try {
        var supported = false;
        if (window['localStorage'] !== null) {
            supported = true;
        }
        return supported;
    } catch (e) {
        return false;
    }
}

function clearFormData(id) {
    if (!isLocalStorageSupported()) {
        // TODO: Alert the user somehow, perhaps?
        return;
    }
    if (id == undefined || id == '') {
        id = window.location.href;
    }
    localStorage.removeItem("form_" + id);
}


function saveFormData(id) {
    if (!isLocalStorageSupported()) {
        // TODO: Alert the user somehow, perhaps?
        return;
    }
    try {
        if (id == undefined || id == '') {
            id = window.location.href;
        }
        localStorage.setObject("form_" + id, encodeFormsAsJSON());
    } catch (e) {
        if (e == QUOTA_EXCEEDED_ERR) {
            alert("Too much stored form data");
        }
    }
}

var simpleTextElements = ['text' , 'textarea', 'password', 'datetime' , 'datetime-local' , 'date' , 'month' , 'week' , 'time' , 'number' , 'range' , 'email' , 'url'];

function recoverFormData(id) {

    if (!isLocalStorageSupported()) {
        // TODO: Alert the user somehow, perhaps?
        return;
    }

    if (id == undefined || id == '') {
        id = window.location.href;
    }
    var formData = localStorage.getObject("form_" + id);

    for (var formName in formData) {
        var form = document.getElementById(formName);
        for (var formElementName in formData[formName]) {

            var formElemnt = form.elements[formElementName];
            var type = formElemnt.type.toLowerCase();

            if( simpleTextElements.indexOf(type) >= 0 ) {

                formElemnt.value = formData[formName][formElementName];

            } else if( type == 'radio' || type == 'checkbox' ) {

                formElemnt.checked = formData[formName][formElementName] == 1;

            } else if( type == 'select-one' ) {

                formElemnt.selectedIndex = formData[formName][formElementName];

            } else if( type == 'select-multiple' ) {

                for (var option in formData[formName][formElementName]) {
                    var optionIdx = formData[formName][formElementName][option];
                    formElemnt.options[optionIdx].selected = true;
                }

            }
        }
    }

}

function encodeFormsAsJSON() {

    var forms = document.forms,
        formsIdx = document.forms.length,
        form,
        formElementIdx = 0,
        formElemnt,
        formElemntType,
        encodedForms = {},
        formElementOptionsIdx
        ;

    while (formsIdx--) {

        form = forms[formsIdx];
        if (form.id == '') {
            continue;
        }
        encodedForms[form.id] = {};
        formElementIdx = form.elements.length;

        while (formElementIdx--) {

            formElemnt = form.elements[formElementIdx];

            if (formElemnt.type && formElemnt.name) {

                formElemntType = formElemnt.type.toLowerCase();

                if( simpleTextElements.indexOf(formElemntType) >= 0 ) {

                    encodedForms[form.id][formElemnt.name] = formElemnt.value;

                } else if( formElemntType == 'radio' || formElemntType == 'checkbox' ) {

                    encodedForms[form.id][formElemnt.name] = formElemnt.checked ? '1' : '';

                } else if( formElemntType == 'select-one' ) {

                    encodedForms[form.id][formElemnt.name] = formElemnt.selectedIndex;

                } else if( formElemntType == 'select-multiple' ) {

                    encodedForms[form.id][formElemnt.name] = [];
                    formElementOptionsIdx = formElemnt.options.length;

                    while (formElementOptionsIdx--) {
                        if (formElemnt.options[formElementOptionsIdx].selected) {
                            encodedForms[form.id][formElemnt.name].push(formElementOptionsIdx);
                        }
                    }

                }

            }
        }

    }

	return encodedForms;

}

if (isLocalStorageSupported()) {

    var startAutoSave = true;
    var rDiv = document.getElementById('recover');
    if (rDiv) {
        
        var value = localStorage.getItem("form_" + window.location.href);
        if (value) {
            startAutoSave = false;
            rDiv.innerHTML = 'You appear to have an unsaved form entry here. <a href="javascript:recoverLink()">Click to recover...</a> or <a href="javascript:discardLink()">discard content.</a>. You should either <strong>recover or discard else autosave will not be active</strong> and you could lose data!';
            rDiv.style.display = 'block';
        }
        
    }

    if (startAutoSave) {
        autoSave();
    }

}

function addEvent(obj, evType, fn) {
    if (obj.addEventListener) {
        obj.addEventListener(evType, fn, false);
        return true;
    } else if (obj.attachEvent) {
        var r = obj.attachEvent("on" + evType, fn);
        return r;
    } else {
        return false;
    }
}

// auto save will only actually start saving once a form element has been changed. Otherwise, why bother?
// Would just cause a build up of useless form data.
function autoSave() {
    for (var i in document.forms) {
        for (var ei in document.forms[i].elements) {
            if( document.forms[i].elements[ei].type && document.forms[i].elements[ei].name) {
                document.forms[i].elements[ei].onchange = function() {
                    clearAutoSaveTrigger();
                    setInterval(function(){
                        saveFormData();
                    }, 1000);
                };
            }
        }
    }
}

function clearAutoSaveTrigger() {
    for (var i in document.forms) {
        for (var ei in document.forms[i].elements) {
            document.forms[i].elements[ei].onchange = function(){};
        }
    }
}


function recoverLink() {
    recoverFormData();
    autoSave();
    var rDiv = document.getElementById('recover');
    rDiv.style.display = 'none';
    rDiv.innerHTML = '';
}
function discardLink() {
    clearFormData();
    autoSave();
    var rDiv = document.getElementById('recover');
    rDiv.style.display = 'none';
    rDiv.innerHTML = '';
}
