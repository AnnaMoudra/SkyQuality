

$(function () {
    $(document).on('click', '.ajax', function (e) {
        e.preventDefault();
        $.get($(this).attr('href'));
    });

    var trackEvent = function (category, action, label, value) {
        ga('send', 'event', category, action, label, value);
    };

    var initScroller = function () {
        $('#left-menu').scrollTop($('#left-menu').find('.active').closest('.nav-sidebar').position().top - 20);
    };
    initScroller();
    $(window).on('resize', function () {
        initScroller();
    });
    $(window).on('ajaxStop', function () {
        initScroller();
    });

    //highlighting and line numbers
    var highlight = function ($pre) {
        var code = $.trim($pre.text());

        //definitions
        code = code.replace(new RegExp("function ([a-zA-Z0-9_]+)\s?", "g"), 'function <span Đ="definition">$1</span>');
        code = code.replace(new RegExp("class ([a-zA-Z0-9_]+)\s?", "g"), 'class <span Đ="definition">$1</span>');

        //variables
        code = code.replace(/(\$[a-zA-Z0-9_]+)/g, '<span Đ="variable">$1</span>');

        //methods
        code = code.replace(/->([a-zA-Z0-9_]+)\(/g, '-><span Đ="method">$1</span>(');

        //properties
        code = code.replace(/->([a-zA-Z0-9_]+)/g, '-><span Đ="property">$1</span>');

        //line comments
        code = code.replace(new RegExp("//([^!\n]+)\n", "g"), '<span Đ="comment">//$1</span>\n');

        //important comments
        code = code.replace(new RegExp("//!([^\n]+)\n", "g"), '<span Đ="important-comment">//$1</span>\n');

        //class constants
        code = code.replace(new RegExp("::([A-Z0-9_]+)", "g"), '::<span Đ="cls-constant">$1</span>');

        //strings
        code = code.replace(new RegExp("('[^']+')", "g"), '<span Đ="string">$1</span>');

        //magic constants
        var constants = ['__LINE__', '__FILE__', '__DIR__', '__FUNCTION__', '__CLASS__', '__TRAIT__', '__METHOD__', '__NAMESPACE__'];

        for (var i = 0; i < constants.length - 1; i++) {
            code = code.replace(new RegExp("\\b" + constants[i] + "\\b", "g"), '<span Đ="constant">' + constants[i] + '</span>');
        }

        var keywords = ['FALSE', 'TRUE', 'false', 'true', '__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch',
            'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach',
            'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements',
            'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected',
            'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield'];

        for (var j = 0; j < keywords.length - 1; j++) {
            code = code.replace(new RegExp("\\b" + keywords[j] + "\\b", "g"), '<span Đ="keyword">' + keywords[j] + '</span>');
        }

        code = code.replace(new RegExp("Đ", "g"), 'class');

        $pre.html(code);
    };

    $('pre.php').each(function () {
        var $this = $(this);
        highlight($this);
        $this.niceCodeLines({
            wrapperClass: 'code-well',
            scrollAbout: -50,
            applyHashAfterReady: false,
            urlHashMatch: function (instance) {
                $(this).addClass('open');
                instance.findByHash();
            }
        });
    });

    $('pre.line').each(function () {
        $(this).niceCodeLines({
            wrapperClass: 'code-well',
            scrollAbout: -50,
            applyHashAfterReady: false,
            urlHashMatch: function (instance) {
                $(this).addClass('open');
                instance.findByHash();
            }
        });
    });

    $('.code-well').each(function () {
        var $this = $(this),
            full_height = $this.outerHeight();

        if (full_height <= 100 || $this.hasClass('open')) return;

        $this.outerHeight(100);
        $this.on('click', function (e, callback) {
            if ($this.outerHeight() === 100) {
                trackEvent('code', 'open', location.pathname, $this.find('pre').attr('data-box'));
                $this.addClass('open').animate({height: full_height}, 500, callback);
            }
        });
        $this.append('<div class="shadow"></div>')
    });
});