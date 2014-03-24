(function(obj, name) {

    var RC = function(flags) {
        return new RChain(flags);
    };


    RC.escape = function(str) {
        return str.replace(/[\/\\\^\[\]\.\$\{\}\*\+\(\)\|\?\<\>]/g, '\\$&');
    };

    RC.compileArr = function(items, groups) {
        return items.map(function(a, i) {
            return RC.compile(a, items[i + 1] && items[i + 1].type == 'repeat', groups);
        }).join('')
    }

    RC.compile = function(item, isRepeatNext, groups) {
        switch (item.type) {
            case 'range':
                return '[' + (item.not ? '^' : '') + item.data + ']'
            case 'group':
                groups.push(item.name)
                return '(' + (item.assertion || "") + item.items.map(function(a) {
                    return RC.compileArr(a, groups);
                }).join('|') + ')';
            case 'raw':
            case 'text':
                if (isRepeatNext && (item.code.length > 1 && !(item.code.length == 2 && item.code[0] == '\\'))) {
                    return '(?:' + item.code + ')';
                } else {
                    return item.code;
                }
            case 'any':
                return '.';
            case 'start':
                return '^';
            case 'end':
                return '$';
            case 'repeat':
                return item.code;
        }
    };

    var RChain = function(flags) {
        if (!Array.isArray(flags)) {
            this.chain = flags.chain.concat([]);
            this.flags = flags.flags;
        } else {
            this.chain = [];
            this.flags = flags || "";
        }
        this.groups = ["match"];
    };

    RChain.prototype = {
        _: function(item) {
            this.push(item);
            return this;
        },
        clone: function() {
            return new RChain(this);
        },
        start: function() {
            return this._({
                type: 'start'
            })
        },
        end: function() {
            return this._({
                type: 'end'
            })
        },
        range: function(allowed, escape) {
            escape = (typeof(escape) === 'undefined' || escape === true) ? true : false;
            var str = escape ? RC.escape(allowed) : escape;
            return this._({
                type: 'range',
                data: str
            });
        },
        notRange: function(allowed) {
            escape = (typeof(escape) === 'undefined' || escape === true) ? true : false;
            var str = escape ? RC.escape(allowed) : escape;
            return this._({
                type: 'range',
                not: true,
                data: str
            });
        },
        group: function(name, assertion, items) {
            items = typeof(items) == 'undefined' ? typeof(assertion) == 'undefined' ? name : assertion : items;
            assertion = items == assertion ? false : (assertion === true ? '?:' : assertion);
            name = typeof(name) == 'string' ? name : false;
            if (!Array.isArray(items)) items = [items];
            return this._({
                type: 'group',
                assertion: assertion,
                items: items.map(function(a) {
                    return a.chain;
                }),
                name: name
            });
        },
        any: function() {
            return this._({
                type: 'any'
            });
        },
        repeat: function(min, max) {
            if (min == "?" || min == "*" || min == '+') {
                return this._({
                    type: 'repeat',
                    code: min
                });
            } else {
                return this._({
                    type: 'repeat',
                    code: '{' + (min || '0') + ',' + (max || "") + '}'
                });
            }
        },
        raw: function(code) {
            return this._({
                type: 'raw',
                code: code
            });
        },
        text: function(text, escape) {
            return this._({
                type: 'text',
                code: (escape === true || typeof(code) == 'undefined') ? RC.escape(text) : text
            });
        },
        compile: function() {
            if (!this._compiled) this._compiled = new RegExp(RC.compileArr(this.chain, this.groups), this.flags);
            return this._compiled;
        },
        exec: function(str) {
            var reg = this.compile();
            var res = reg.exec(str);
            var nRes = {};
            for (var i = 0; i < res.length; i++) {
                nRes[i] = res[i];
                if (this.groups[i]) {
                    nRes[this.groups[i]] = res[i];
                }
            }
            nRes._index = res.index;
            nRes._input = res.input;
            return nRes;
        }
    };
    obj[name] = RC;


})(typeof(module) === 'undefined' ? window : module, typeof(module) === 'undefined' ? 'RC' : 'exports');