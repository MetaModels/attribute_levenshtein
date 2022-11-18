/**
 * This file is part of MetaModels/attribute_levenshtein.
 *
 * (c) 2012-2022 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_levenshtein
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

(function() {
    this.AutoComplete = function() {
        this.minChar    = 1;
        this.selector   = '';
        this.eventArr   = [];
        this.url        = '';
        this.search     = '';
        this.param      = '';
        this.method     = '';
        this.autoSubmit = 0;
        this.debug      = false

        // Define option defaults.
        let defaults = {
            minChar   : 1,
            selector  : '',
            url       : '',
            method    : 'GET',
            param     : '',
            autoSubmit: 0,
            debug     : false,
        };

        if (arguments[0] && typeof arguments[0] === 'object') {
            this.options = extendDefaults(defaults, arguments[0]);
        }

        this.minChar    = this.options.minChar;
        this.selector   = this.options.selector;
        this.url        = this.options.url;
        this.param      = this.options.param;
        this.method     = this.options.method;
        this.autoSubmit = this.options.autoSubmit;
        this.debug      = this.options.debug;

        this.autocomplete();
        hideStyle(this);
    };

    AutoComplete.prototype.autocomplete = function() {
        let __self         = this;
        let __autocomplete = document.querySelector('#' + __self.selector + ' input');

        __autocomplete.addEventListener('keyup', function(e) {
            let __value = document.querySelector('#' + __self.selector + ' input').value;

            if (__value !== undefined && __value !== null && __value.length >= __self.minChar) {
                if(__self.debug) {console.log('__autocomplete term:  ', __value);}
                getResult(__self, __value);
            }
        });
    };

    // Utility method to extend defaults with user options.
    function extendDefaults(source, properties) {
        let property;
        for (property in properties) {
            if (properties.hasOwnProperty(property)) {
                source[property] = properties[property];
            }
        }

        return source;
    }

    function getResult(__this, __value) {
        let xhttp = new XMLHttpRequest();

        if (__this.method === 'GET' || __this.method === 'get') {
            xhttp.open('GET', __this.url + '?search=' + __value+__this.param, true);
        } else {
            xhttp.open('POST', __this.url, true);
        }

        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                let text = xhttp.responseText;
                let json;

                if(__this.debug) {console.log('responseText: ', text);}

                // Support both plain text and JSON responses.
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    json = {'content': text};
                }

                // Empty response.
                if (json === null) {
                    json = {'content': ''};
                } else {
                    if (typeof (json) != 'object') {
                        json = {'content': text};
                    }
                }

                if(__this.debug) {console.log('result: ', json);}

                if (getHtmlTemplate(json) != '') {
                    document.querySelector('#' + __this.selector + ' > .result__container > ul').innerHTML =
                        getHtmlTemplate(json);
                    setStyle(__this);
                    initializeEvents(__this);
                }
            } else {
                if (xhttp.status !== 200) {
                    if(__this.debug) {console.log(xhttp.status);}
                }
            }
        };

        if (__this.method === 'GET' || __this.method === 'get') {
            xhttp.send();
        } else {
            xhttp.send(__this.param);
        }
    } // End get result.

    function getHtmlTemplate(resultObj) {
        let str = '';

        if (resultObj !== undefined && resultObj !== null) {
            if (resultObj.length > 0) {
                for (let i = 0; i < resultObj.length; i++) {
                    str += '<li data-value="' + resultObj[i].value + '">' + resultObj[i].label + '</li>';
                }
            }
        }

        return str;
    }

    function initializeEvents(_prop) {
        let _actionElems = document.querySelectorAll('#' + _prop.selector + ' ul > li');

        // Bind click event.
        for (let i = 0; i < _actionElems.length; i++) {
            _actionElems[i].addEventListener('click', function() {
                document.querySelector('#' + _prop.selector + ' > input').value = this.getAttribute('data-value');
                document.querySelector('#' + _prop.selector + ' > .result__container ul').innerHTML = '';
                document.querySelector('#' + _prop.selector + ' > .result__container > ul').style.display = 'none';
                if('1' === _prop.autoSubmit) {
                    document.querySelector('#' + _prop.selector).closest('form').submit();
                }
            });
        }
    }

    function hideStyle(__this) {
        document.onclick = function(event) {
            let hasParent = false;
            for (let node = event.target; node !== null && node !== document.body; node = node.parentNode) {
                if (node.id === __this.selector) {
                    hasParent = true;
                    break;
                }
            }

            if (hasParent) {
            } else {
                document.querySelector('#' + __this.selector + ' > .result__container ul').innerHTML       = '';
                document.querySelector('#' + __this.selector + ' > .result__container > ul').style.display = 'none';
            }
        };
    }

    function setStyle(__this) {
        let elmInput = document.querySelector('#' + __this.selector + ' > input');
        document.querySelector('#' + __this.selector + ' > .result__container > ul').style.width   =
            elmInput.offsetWidth + 'px';
        document.querySelector('#' + __this.selector + ' > .result__container > ul').style.display = 'block';
    }
}());
