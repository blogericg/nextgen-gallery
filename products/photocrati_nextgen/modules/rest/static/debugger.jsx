import ReactDOM from 'react-dom';
import React, {Fragment} from 'react';

import Ajv from "ajv";

//  No license is provided; credit is given to http://phpjs.org/functions/var_dump/
function print_formatted_array() {
    var output = '',
        pad_char = ' ',
        pad_val = 2,
        lgth = 0,
        i = 0;

    var _getFuncName = function(fn) {
        var name = (/\W*function\s+([\w\$]+)\s*\(/)
            .exec(fn);
        if (!name) {
            return '(Anonymous)';
        }
        return name[1];
    };

    var _repeat_char = function(len, pad_char) {
        var str = '';
        for (var i = 0; i < len; i++) {
            str += pad_char;
        }
        return str;
    };
    var _getInnerVal = function(val, thick_pad) {
        var ret = '';
        if (val === null) {
            ret = 'NULL';
        } else if (typeof val === 'boolean') {
            ret = 'bool(' + val + ')';
        } else if (typeof val === 'string') {
            ret = 'string(' + val.length + ') "' + val + '"';
        } else if (typeof val === 'number') {
            if (parseFloat(val) == parseInt(val, 10)) {
                ret = 'int(' + val + ')';
            } else {
                ret = 'float(' + val + ')';
            }
        }
        // The remaining are not PHP behavior because these values only exist in this exact form in JavaScript
        else if (typeof val === 'undefined') {
            ret = 'undefined';
        } else if (typeof val === 'function') {
            var funcLines = val.toString()
                .split('\n');
            ret = '';
            for (var i = 0, fll = funcLines.length; i < fll; i++) {
                ret += (i !== 0 ? '\n' + thick_pad : '') + funcLines[i];
            }
        } else if (val instanceof Date) {
            ret = 'Date(' + val + ')';
        } else if (val instanceof RegExp) {
            ret = 'RegExp(' + val + ')';
        } else if (val.nodeName) {
            // Different than PHP's DOMElement
            switch (val.nodeType) {
                case 1:
                    if (typeof val.namespaceURI === 'undefined' || val.namespaceURI === 'http://www.w3.org/1999/xhtml') {
                        // Undefined namespace could be plain XML, but namespaceURI not widely supported
                        ret = 'HTMLElement("' + val.nodeName + '")';
                    } else {
                        ret = 'XML Element("' + val.nodeName + '")';
                    }
                    break;
                case 2:
                    ret = 'ATTRIBUTE_NODE(' + val.nodeName + ')';
                    break;
                case 3:
                    ret = 'TEXT_NODE(' + val.nodeValue + ')';
                    break;
                case 4:
                    ret = 'CDATA_SECTION_NODE(' + val.nodeValue + ')';
                    break;
                case 5:
                    ret = 'ENTITY_REFERENCE_NODE';
                    break;
                case 6:
                    ret = 'ENTITY_NODE';
                    break;
                case 7:
                    ret = 'PROCESSING_INSTRUCTION_NODE(' + val.nodeName + ':' + val.nodeValue + ')';
                    break;
                case 8:
                    ret = 'COMMENT_NODE(' + val.nodeValue + ')';
                    break;
                case 9:
                    ret = 'DOCUMENT_NODE';
                    break;
                case 10:
                    ret = 'DOCUMENT_TYPE_NODE';
                    break;
                case 11:
                    ret = 'DOCUMENT_FRAGMENT_NODE';
                    break;
                case 12:
                    ret = 'NOTATION_NODE';
                    break;
            }
        }
        return ret;
    };

    var _formatArray = function(obj, cur_depth, pad_val, pad_char) {
        var someProp = '';
        if (cur_depth > 0) {
            cur_depth++;
        }

        var base_pad = _repeat_char(pad_val * (cur_depth - 1), pad_char);
        var thick_pad = _repeat_char(pad_val * (cur_depth + 1), pad_char);
        var str = '';
        var val = '';

        if (typeof obj === 'object' && obj !== null) {
            if (obj.constructor && _getFuncName(obj.constructor) === 'PHPJS_Resource') {
                return obj.var_dump();
            }
            lgth = 0;
            for (someProp in obj) {
                lgth++;
            }
            str += 'array(' + lgth + ') {\n';
            for (var key in obj) {
                var objVal = obj[key];
                if (typeof objVal === 'object' && objVal !== null && !(objVal instanceof Date) && !(objVal instanceof RegExp) &&
                    !
                        objVal.nodeName) {
                    str += thick_pad + '[' + key + '] =>\n' + thick_pad + _formatArray(objVal, cur_depth + 1, pad_val,
                        pad_char);
                } else {
                    val = _getInnerVal(objVal, thick_pad);
                    str += thick_pad + '[' + key + '] =>\n' + thick_pad + val + '\n';
                }
            }
            str += base_pad + '}\n';
        } else {
            str = _getInnerVal(obj, thick_pad);
        }
        return str;
    };

    output = _formatArray(arguments[0], 0, pad_val, pad_char);
    for (i = 1; i < arguments.length; i++) {
        output += '\n' + _formatArray(arguments[i], 0, pad_val, pad_char);
    }

    return output;
}

class LinksDisplay extends React.Component {
    add_endpoint = (link) => {
        const url = link.replace(window.ngg_rest_debugger_url, '');
        this.props.add_endpoint({endpoint: url});
    };

    render() {
        return Object.keys(this.props.links).map((key, ndx) =>
            <Fragment key={"link-fragment-" + ndx}>
                <a key={"link-" + ndx}
                   href={this.props.links[key].href}
                   onClick={((event) => { event.preventDefault(); this.add_endpoint(this.props.links[key].href)})}>
                    {key}
                </a>
                <br/>
            </Fragment>
        );
    }
}

class TableOutput extends React.Component {
    render() {
        let headers = [];
        let rows = [];

        this.props.values.map((item, itemndx) => {
            if (headers.length === 0) {
                headers.push(
                    <tr key="header-tr-0">
                        {Object.keys(item).map((key, ndx) =>
                            <th key={"header-th-" + itemndx + "-" + ndx}>{key}</th>)}
                    </tr>
                );
            }

            rows.push(
                <tr key={"value-tr-" + itemndx}>
                    {Object.values(item).map((key, ndx) =>
                        <td key={"value-tdx-" + itemndx + "-" + ndx}>
                            {('object' === typeof(key)) ? (Object.keys(item)[ndx] === '_links' ? <LinksDisplay add_endpoint={this.props.add_endpoint} links={key} /> : <xmp> {print_formatted_array(key)} </xmp>) : key}
                        </td>
                    )}
                </tr>
            );
        });


        return (
            <div>
                <button onClick={() => this.props.updater(this.props.resource_type)}>
                    Debug /{this.props.resource_type.endpoint}
                </button>
                &nbsp;
                <button onClick={() => this.props.close(this.props.resource_type)}>
                    Close /{this.props.resource_type.endpoint}
                </button>
                <table>
                    <thead>
                        {headers[0]}
                    </thead>
                    <tbody>
                        {rows}
                    </tbody>
                </table>
                <hr/>
            </div>
        );
    }
}

class NGGDebugRester extends React.Component {

    // state and resource types share identifying keys
    state = {
        galleries: [],
        albums: [],
        settings: [],
        display_types: []
    }

    resource_types = [
        { endpoint: "galleries" },
        { endpoint: "albums" },
        { endpoint: "settings" },
        { endpoint: "display_types" }
    ]

    fetch_update = (resource_type) => {
        const url = window.ngg_rest_debugger_url + resource_type.endpoint;
        const self = this;

        // First make an OPTIONS request to get the schema to validate and validate all responses automatically
        // Yes this could be made neater but this is a debug panel not meant for production and I'm just hammering
        // out the bare basics here.
        fetch(url, {
            method: 'OPTIONS',
            headers: {
                'X-WP-Nonce': window.ngg_rest_debugger_nonce
            }
        })
            .then(otherresponse => otherresponse.json())
            .then(function(optionsdata) {
                const ajv = new Ajv();
                const schema = optionsdata.schema;
                try {
                    const ajv_validate = ajv.compile(schema)
                    fetch(url, {
                        headers: {
                            'X-WP-Nonce': window.ngg_rest_debugger_nonce
                        }
                    })
                        .then(response => response.json())
                        .then(function(responsedata) {
                            // I can't think of an easy and elegant way to flag these results so we'll just dump them into the console for now
                            const valid = ajv_validate(responsedata)
                            if (!valid) {
                                console.log(url + " Validation failure", ajv_validate.errors);
                            } else {
                                console.log(url + " Validation passed");
                            }

                            // minor hack to account for non-array results
                            if (responsedata instanceof Array) {
                                self.setState({[resource_type.endpoint]: responsedata});
                            } else {
                                self.setState({[resource_type.endpoint]: [responsedata]});
                            }
                        });

                } catch (exception) {
                    console.log(url + " Schema error:", exception, typeof exception);
                }
            });

    }

    clear_endpoint = (endpoint) => {
        this.state[endpoint.endpoint] = [];
        this.setState(this.state);
    }

    add_endpoint = (endpoint) => {
        if (this.state[endpoint.endpoint])
            return;
        this.resource_types.push(endpoint);
        this.state[endpoint.endpoint] = [];
        this.setState(this.state);
    }

    render() {
        return (
            <Fragment>
                {this.resource_types.map((resource_type) =>
                    <TableOutput resource_type={resource_type}
                                 updater={this.fetch_update}
                                 add_endpoint={this.add_endpoint}
                                 key={resource_type.endpoint}
                                 close={this.clear_endpoint}
                                 values={this.state[resource_type.endpoint]}/>
                )}
            </Fragment>
        );
    }
}

export function init(node) {
    ReactDOM.render(
        <NGGDebugRester/>,
        node
    );
}

window.initNGGRestDebugger = init;