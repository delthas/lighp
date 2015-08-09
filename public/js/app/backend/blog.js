(function() {
	/**
	 * JavaScript code to detect available availability of a
	 * particular font in a browser using JavaScript and CSS.
	 *
	 * Author : Lalit Patel
	 * Website: http://www.lalit.org/lab/javascript-css-font-detect/
	 * License: Apache Software License 2.0
	 *          http://www.apache.org/licenses/LICENSE-2.0
	 * Version: 0.15 (21 Sep 2009)
	 *          Changed comparision font to default from sans-default-default,
	 *          as in FF3.0 font of child element didn't fallback
	 *          to parent element if the font is missing.
	 * Version: 0.2 (04 Mar 2012)
	 *          Comparing font against all the 3 generic font families ie,
	 *          'monospace', 'sans-serif' and 'sans'. If it doesn't match all 3
	 *          then that font is 100% not available in the system
	 * Version: 0.3 (24 Mar 2012)
	 *          Replaced sans with serif in the list of baseFonts
	 */

	/**
	 * Usage: d = new Detector();
	 *        d.detect('font name');
	 */
	var Detector = function() {
		// a font will be compared against all the three default fonts.
		// and if it doesn't match all 3 then that font is not available.
		var baseFonts = ['monospace', 'sans-serif', 'serif'];

		//we use m or w because these two characters take up the maximum width.
		// And we use a LLi so that the same matching fonts can get separated
		var testString = "mmmmmmmmmmlli";

		//we test using 72px font size, we may use any size. I guess larger the better.
		var testSize = '72px';

		var h = document.getElementsByTagName("body")[0];

		// create a SPAN in the document to get the width of the text we use to test
		var s = document.createElement("span");
		s.style.fontSize = testSize;
		s.innerHTML = testString;
		var defaultWidth = {};
		var defaultHeight = {};
		for (var index in baseFonts) {
			//get the default width for the three base fonts
			s.style.fontFamily = baseFonts[index];
			h.appendChild(s);
			defaultWidth[baseFonts[index]] = s.offsetWidth; //width for the default font
			defaultHeight[baseFonts[index]] = s.offsetHeight; //height for the defualt font
			h.removeChild(s);
		}

		function detect(font) {
			var detected = false;
			for (var index in baseFonts) {
				s.style.fontFamily = font + ',' + baseFonts[index]; // name of the font along with the base font for fallback.
				h.appendChild(s);
				var matched = (s.offsetWidth != defaultWidth[baseFonts[index]] || s.offsetHeight != defaultHeight[baseFonts[index]]);
				h.removeChild(s);
				detected = detected || matched;
			}
			return detected;
		}

		this.detect = detect;
	};

	window.FontDetector = Detector;
})();

(function() {
	var blogApi = Lighp.registerModule('backend', 'blog');

	var Editor = function(el) {
		this._$el = $(el);

		this._init();
	};
	Editor.prototype = {
		_editorMode: null,

		_$el: $(),

		_init: function() {
			var that = this;

			if (!Editor.checkHtml5Support()) {
				this.execCommand('switchMode', 'html');
				return;
			}

			var $editor = this._$el, $editorInner = $editor.find('.editor-inner.editor-wysiwyg');
			var editorData = $editor.data();

			$editor.find('.toolbar-container').show();

			$editor.on('click', 'button,a', function(event) {
				var elData = $(this).data();

				if (elData.cmd) {
					that.execCommand(elData.cmd, elData.arg);
				}

				event.preventDefault();
			});

			$editor.one('focus', function() {
				that.execCommand('enableObjectResizing');
			});

			var $toolbarContainer = $editor.find('.toolbar-container');
			var toolbarOffset = $toolbarContainer.offset();
			$(window).scroll(function(event) {
				var scrollTop = $(window).scrollTop();

				var topDiff = scrollTop - toolbarOffset.top;
				if (topDiff > 0) {
					if (!$toolbarContainer.is('.toolbar-fixed')) {
						$toolbarContainer.addClass('toolbar-fixed');
					}
					$toolbarContainer.css('top', topDiff);
				} else if ($toolbarContainer.is('.toolbar-fixed')) {
					$toolbarContainer.removeClass('toolbar-fixed');
				}
			});

			$editor.parents('form').first().submit(function(event) {
				that.getEditor('html').val(that.getValue().replace(/\n/g, ''));
			});

			this.getEditor('html').on('keyup mouseup change cut paste drop', function() {
				var $htmlEditor = $(this);

				var height = $htmlEditor[0].scrollHeight + ($htmlEditor.height() - $htmlEditor.outerHeight());
				$htmlEditor.css('height', height + 'px');
			});

			this.execCommand('switchMode', 'wysiwyg');

			this._initFonts();
		},
		_initFonts: function() {
			var that = this;

			this._$el.find('button,a').each(function() {
				var elData = $(this).data();

				if (elData.cmd && elData.cmd.toLowerCase() == 'fontname' && elData.arg) {
					var fontName = elData.arg;

					if (!that.detectFont(fontName)) {
						if ($(this).parent().is('li')) {
							$(this).parent().hide();
						} else {
							$(this).hide();
						}
					} else {
						$(this).css('font-family', fontName);
					}
				}
			});
		},

		editorMode: function () {
			return this._editorMode;
		},
		getEditor: function (mode) {
			if (!mode) {
				mode = this.editorMode();
			}

			return this._$el.find('.editor-inner.editor-' + mode);
		},
		getValue: function(mode) {
			var $editor = this.getEditor(mode);

			return $editor[($editor.is('textarea')) ? 'val' : 'html']();
		},
		//From http://jsfiddle.net/timdown/gEhjZ/4/
		saveSelection: function () { 
			var containerEl = this.getEditor()[0];

			if (window.getSelection && document.createRange) {
				var doc = containerEl.ownerDocument, win = doc.defaultView;
				var range = win.getSelection().getRangeAt(0);
				var preSelectionRange = range.cloneRange();
				preSelectionRange.selectNodeContents(containerEl);
				preSelectionRange.setEnd(range.startContainer, range.startOffset);
				var start = preSelectionRange.toString().length;

				return {
					start: start,
					end: start + range.toString().length
				};
			} else {
				var doc = containerEl.ownerDocument, win = doc.defaultView || doc.parentWindow;
				var selectedTextRange = doc.selection.createRange();
				var preSelectionTextRange = doc.body.createTextRange();
				preSelectionTextRange.moveToElementText(containerEl);
				preSelectionTextRange.setEndPoint("EndToStart", selectedTextRange);
				var start = preSelectionTextRange.text.length;

				return {
					start: start,
					end: start + selectedTextRange.text.length
				};
			}
		},
		restoreSelection: function (savedSel) {
			var containerEl = this.getEditor()[0];

			if (window.getSelection && document.createRange) {
				var doc = containerEl.ownerDocument, win = doc.defaultView;
				var charIndex = 0, range = doc.createRange();
				range.setStart(containerEl, 0);
				range.collapse(true);
				var nodeStack = [containerEl], node, foundStart = false, stop = false;

				while (!stop && (node = nodeStack.pop())) {
					if (node.nodeType == 3) {
						var nextCharIndex = charIndex + node.length;
						if (!foundStart && savedSel.start >= charIndex && savedSel.start <= nextCharIndex) {
							range.setStart(node, savedSel.start - charIndex);
							foundStart = true;
						}
						if (foundStart && savedSel.end >= charIndex && savedSel.end <= nextCharIndex) {
							range.setEnd(node, savedSel.end - charIndex);
							stop = true;
						}
						charIndex = nextCharIndex;
					} else {
						var i = node.childNodes.length;
						while (i--) {
							nodeStack.push(node.childNodes[i]);
						}
					}
				}

				var sel = win.getSelection();
				sel.removeAllRanges();
				sel.addRange(range);
			} else {
				var doc = containerEl.ownerDocument, win = doc.defaultView || doc.parentWindow;
				var textRange = doc.body.createTextRange();
				textRange.moveToElementText(containerEl);
				textRange.collapse(true);
				textRange.moveEnd("character", savedSel.end);
				textRange.moveStart("character", savedSel.start);
				textRange.select();
			}
		},
		execCommand: function(cmd, arg) {
			if (typeof Editor._commands[cmd] == 'function') {
				Editor._commands[cmd].call(this, arg);
			} else {
				if (this.editorMode() != 'wysiwyg') {
					return;
				}

				this._$el.find('.editor-wysiwyg').focus();
				var isSuccess = document.execCommand(cmd, false, arg || null);

				if (!isSuccess) {
					if (document.queryCommandSupported && document.queryCommandSupported(cmd)) {
						return;
					}

					this.showAlert('Impossible d\'ex&eacute;cuter la commande "'+cmd+'".', 'error');
				}
			}
		},
		showAlert: function(msg, type) {
			var $toolbarContainer = this._$el.find('.toolbar-container');
			var $alert = $toolbarContainer.children('.alert');

			if ($alert.length) {
				$alert.slideUp('fast', function() {
					$(this).remove();
				});
			}

			$alert = $('<p class="alert"></p>')
				.append('<button type="button" class="close" data-dismiss="alert">&times;</button>')
				.append(msg)
				.hide()
				.appendTo($toolbarContainer);

			if (type) {
				$alert.addClass('alert-'+type);
			}

			$alert.slideDown('fast');

			setTimeout(function() {
				$alert.slideUp('fast', function() {
					$(this).remove();
				});
			}, 5000);
		},
		detectFont: function(fontName) {
			var detector = new FontDetector();
			return detector.detect(fontName);
		}
	};

	Editor._modes = {
		wysiwyg: {
			title: 'WYSIWYG'
		},
		html: {
			title: 'HTML'
		}
	};

	Editor._commands = {
		createCustomLink: function(arg) {
			var that = this;

			if (arg) {
				this.execCommand('createLink', arg);
			} else {
				var sel = this.saveSelection();

				$('#editor-modal-createCustomLink').modal('show');
				$('#editor-modal-createCustomLink').find('.btn-primary').one('click', function() {
					$('#editor-modal-createCustomLink').modal('hide');
					var linkUrl = $('#createCustomLink-content').val(),
					linkText = $('#createCustomLink-text').val();

					that.restoreSelection(sel);
					if (linkText) {
						var htmlToInsert = '<a href="'+linkUrl+'">'+linkText+'</a> ';
						that.execCommand('insertHTML', htmlToInsert);
					} else {
						that.execCommand('createLink', linkUrl);
					}
				});
				$('#createCustomLink-content').focus();
			}
		},
		insertCustomImage: function(arg) {
			var that = this;

			if (arg) {
				this.execCommand('insertImage', arg);
			} else {
				var sel = this.saveSelection();
				var $modal = $('#editor-modal-insertCustomImage'),
				$urlInput = $('#insertCustomImage-url'),
				$fileInput = $('#insertCustomImage-file'),
				$captionInput = $('#insertCustomImage-caption'),
				$thumbnails = $modal.find('.thumbnails');

				$urlInput.val('');
				$fileInput.val('');
				$thumbnails.empty();

				$modal.modal('show');
				$modal.find('#insertCustomImage-form').one('submit', function(event) {
					event.preventDefault();
					$fileInput.off('change');

					$modal.modal('hide');
					var imgUrl = $urlInput.val(),
					imgFiles = $fileInput[0].files,
					imgCaption = $captionInput.val();

					that.restoreSelection(sel);

					if (imgUrl) {
						that.execCommand('insertHTML', '<img src="'+imgUrl+'" alt="'+imgCaption+'"/>');
					} else if(imgFiles && imgFiles.length) {
						var spinnersIdPrefix = 'imgupload-'+(new Date()).getTime();
						for (var i = 0; i < imgFiles.length; i++) {
							var spinnerId = spinnersIdPrefix+'-'+i;
							var fileName = (imgFiles[i].name) ? imgFiles[i].name : '';
							var spinner = '<span id="'+spinnerId+'" title="Envoi en cours'+((fileName) ? ' de '+fileName : '')+'...">'+Lighp.loading.spinner()+'</span>';

							that.execCommand('insertHTML', spinner);
						}

						var currentFileIndex = 0;

						var processFile = function() {
							if (!window.FormData) {
								Lighp.triggerError('Cannot upload files : outdated Web browser (missing API : FormData)');
								return;
							}

							var fd = new FormData();
							fd.append('image', imgFiles[currentFileIndex]);

							var req = Lighp.ApiRequest.build('/api/admin/blog/images/insert', {
								postData: fd
							});

							req.execute(function(data) {
								var imgPath = Lighp.websiteConf.WEBSITE_ROOT + '/' + data.path;
								$('#'+spinnersIdPrefix+'-'+currentFileIndex).replaceWith('<img src="'+imgPath+'" alt="'+imgCaption+'"/>');

								currentFileIndex++;
								if (imgFiles.length > currentFileIndex) {
									processFile();
								}
							});
						};

						processFile();
					}
				});

				$fileInput.change(function() {
					if (!window.URL || !window.URL.createObjectURL) {
						return;
					}

					var imgFiles = this.files;

					$thumbnails.empty();
					for (var i = 0; i < imgFiles.length; i++) {
						var file = imgFiles[i];
						var imgUrl = window.URL.createObjectURL(file);
						$thumbnails.append('<li class="span1"><div class="thumbnail"><img src="'+imgUrl+'" alt="Image"/></div></li>');
					}
				});

				$urlInput.focus();
			}
		},
		insertCustomHTML: function(arg) {
			var that = this;

			if (arg) {
				this.execCommand('insertHTML', arg);
			} else {
				var sel = this.saveSelection();

				$('#editor-modal-insertCustomHTML').modal('show');
				$('#editor-modal-insertCustomHTML').find('.btn-primary').one('click', function() {
					$('#editor-modal-insertCustomHTML').modal('hide');
					var htmlToInsert = $('#insertCustomHTML-content').val();

					that.restoreSelection(sel);
					if (htmlToInsert) {
						that.execCommand('insertHTML', htmlToInsert);
					}
				});
				$('#insertCustomHTML-content').focus();
			}
		},
		heading: function (headingName) {
			// The "heading" command is only supported in Firefox
			this.execCommand('formatBlock', '<'+headingName+'>');
		},
		switchMode: function(newMode) {
			var $editor = this._$el;

			var newModeData = Editor._modes[newMode];
			if (typeof newModeData == 'undefined') {
				return false;
			}

			var $newEditorInner = $editor.find('.editor-inner.editor-' + newMode),
			$otherInners = $editor.find('.editor-inner:not(.editor-' + newMode + ')');

			if (!$newEditorInner.length) {
				return false;
			}

			if (!Editor.checkHtml5Support() && newMode == 'wysiwyg') {
				return false;
			}

			var previousContent = '';
			if (this._editorMode) {
				var $previousEditorInner = $editor.find('.editor-inner.editor-' + this.editorMode());
				previousContent = $previousEditorInner[($previousEditorInner.is('textarea')) ? 'val' : 'html']();
			} else {
				previousContent = $newEditorInner[($newEditorInner.is('textarea')) ? 'val' : 'html']();
			}

			if (newMode != 'wysiwyg') { // Improve HTML visibility
				previousContent = previousContent
					.replace(/(<br\s?\/?>)([^\n])/gi, '$1\n$2')
					.replace(/(<\/(?:p|div|h[0-6]|ul|ol|li|blockquote|hr|pre|code)>)([^\n])/gi, '$1\n$2');
			}

			$newEditorInner.show();
			$otherInners.hide();
			$newEditorInner[($newEditorInner.is('textarea')) ? 'val' : 'html'](previousContent).trigger('change');

			if (newMode == 'wysiwyg') {
				$editor.find('.toolbar-wysiwyg').css('visibility', 'visible');
			} else {
				$editor.find('.toolbar-wysiwyg').css('visibility', 'hidden');
			}

			$editor.find('.editor-currentMode').html(newModeData.title);
			this._editorMode = newMode;

			$newEditorInner.focus();
		}
	};

	Editor.checkHtml5Support = function() {
		return (typeof $('body')[0].contentEditable != 'undefined');
	};

	Editor._init = function() {
		var $html5Editors = $('.editor-html5');
		if ($html5Editors.length > 0) {
			$html5Editors.each(function() {
				var editor = new Editor(this);
			});
		}
	};

	Editor._init();
})();