<?php
/**
 * Rich Text Editor Component
 * Advanced writing experience for diary entries
 */
?>

<div class="rich-text-editor">
    <!-- Toolbar -->
    <div class="editor-toolbar">
        <!-- Text Formatting -->
        <div class="toolbar-group">
            <button type="button" class="toolbar-btn" data-command="bold" title="Bold (Ctrl+B)">
                <i class="fas fa-bold"></i>
            </button>
            <button type="button" class="toolbar-btn" data-command="italic" title="Italic (Ctrl+I)">
                <i class="fas fa-italic"></i>
            </button>
            <button type="button" class="toolbar-btn" data-command="underline" title="Underline (Ctrl+U)">
                <i class="fas fa-underline"></i>
            </button>
            <button type="button" class="toolbar-btn" data-command="strikeThrough" title="Strikethrough">
                <i class="fas fa-strikethrough"></i>
            </button>
        </div>

        <div class="toolbar-divider"></div>

        <!-- Headers -->
        <div class="toolbar-group">
            <select class="toolbar-select" data-command="formatBlock" title="Text Style">
                <option value="">Normal Text</option>
                <option value="h1">Large Heading</option>
                <option value="h2">Medium Heading</option>
                <option value="h3">Small Heading</option>
                <option value="blockquote">Quote Block</option>
            </select>
        </div>

        <div class="toolbar-divider"></div>

        <!-- Lists -->
        <div class="toolbar-group">
            <button type="button" class="toolbar-btn" data-command="insertUnorderedList" title="Bullet List">
                <i class="fas fa-list-ul"></i>
            </button>
            <button type="button" class="toolbar-btn" data-command="insertOrderedList" title="Numbered List">
                <i class="fas fa-list-ol"></i>
            </button>
            <button type="button" class="toolbar-btn" data-command="outdent" title="Decrease Indent">
                <i class="fas fa-outdent"></i>
            </button>
            <button type="button" class="toolbar-btn" data-command="indent" title="Increase Indent">
                <i class="fas fa-indent"></i>
            </button>
        </div>

        <div class="toolbar-divider"></div>

        <!-- Alignment -->
        <div class="toolbar-group">
            <button type="button" class="toolbar-btn" data-command="justifyLeft" title="Align Left">
                <i class="fas fa-align-left"></i>
            </button>
            <button type="button" class="toolbar-btn" data-command="justifyCenter" title="Align Center">
                <i class="fas fa-align-center"></i>
            </button>
            <button type="button" class="toolbar-btn" data-command="justifyRight" title="Align Right">
                <i class="fas fa-align-right"></i>
            </button>
        </div>

        <div class="toolbar-divider"></div>



        <div class="toolbar-divider"></div>

        <!-- Special Elements -->
        <div class="toolbar-group">
            <button type="button" class="toolbar-btn" data-command="insertHorizontalRule" title="Insert Divider">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="toolbar-btn" onclick="insertQuoteBlock()" title="Insert Quote Block">
                <i class="fas fa-quote-left"></i>
            </button>
            <button type="button" class="toolbar-btn" onclick="insertHighlightBox()" title="Insert Highlight Box">
                <i class="fas fa-highlighter"></i>
            </button>
        </div>

        <div class="toolbar-divider"></div>

        <!-- Undo/Redo -->
        <div class="toolbar-group">
            <button type="button" class="toolbar-btn" data-command="undo" title="Undo (Ctrl+Z)">
                <i class="fas fa-undo"></i>
            </button>
            <button type="button" class="toolbar-btn" data-command="redo" title="Redo (Ctrl+Y)">
                <i class="fas fa-redo"></i>
            </button>
        </div>

        <div class="toolbar-divider"></div>

        <!-- Word Count -->
        <div class="toolbar-group">
            <span class="word-count">Words: <span id="word-counter">0</span></span>
        </div>
    </div>

    <!-- Editor Content Area -->
    <div class="editor-content" 
         contenteditable="true" 
         id="rich-content-editor"
         data-placeholder="Start writing your diary entry... Use the toolbar to format your thoughts beautifully!"
         spellcheck="true">
    </div>

    <!-- Hidden textarea for form submission -->
    <textarea name="content" id="content-textarea" style="display: none;" required></textarea>
</div>

<style>
.rich-text-editor {
    border: 2px solid #e1e5e9;
    border-radius: 12px;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: border-color 0.3s;
}

.rich-text-editor:focus-within {
    border-color: #667eea;
    box-shadow: 0 2px 15px rgba(102, 126, 234, 0.2);
}

.editor-toolbar {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.toolbar-group {
    display: flex;
    align-items: center;
    gap: 4px;
    position: relative;
}

.toolbar-btn {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 8px 10px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
    color: #495057;
    min-width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toolbar-btn:hover {
    background: #e9ecef;
    border-color: #adb5bd;
    transform: translateY(-1px);
}

.toolbar-btn.active {
    background: #667eea;
    border-color: #667eea;
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.toolbar-select {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 14px;
    color: #495057;
    cursor: pointer;
    min-width: 140px;
}

.toolbar-select:focus {
    outline: none;
    border-color: #667eea;
}

.toolbar-divider {
    width: 1px;
    height: 24px;
    background: #dee2e6;
    margin: 0 4px;
}

.toolbar-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1000;
    min-width: 200px;
    display: none;
    margin-top: 4px;
}

.toolbar-dropdown.show {
    display: block;
}



.word-count {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
    padding: 8px 12px;
    background: white;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}

.editor-content {
    min-height: 300px;
    max-height: 600px;
    overflow-y: auto;
    padding: 20px;
    font-size: 16px;
    line-height: 1.6;
    color: #333;
    outline: none;
    background: white;
}

.editor-content:empty:before {
    content: attr(data-placeholder);
    color: #999;
    font-style: italic;
    pointer-events: none;
}

/* Rich Text Styling */
.editor-content h1 {
    font-size: 2em;
    font-weight: bold;
    margin: 16px 0 12px 0;
    color: #333;
    border-bottom: 2px solid #667eea;
    padding-bottom: 8px;
}

.editor-content h2 {
    font-size: 1.5em;
    font-weight: bold;
    margin: 14px 0 10px 0;
    color: #495057;
}

.editor-content h3 {
    font-size: 1.2em;
    font-weight: bold;
    margin: 12px 0 8px 0;
    color: #6c757d;
}

.editor-content blockquote {
    margin: 16px 0;
    padding: 16px 20px;
    border-left: 4px solid #667eea;
    background: #f8f9fa;
    font-style: italic;
    border-radius: 0 8px 8px 0;
}

.editor-content ul, .editor-content ol {
    margin: 12px 0;
    padding-left: 24px;
}

.editor-content li {
    margin: 4px 0;
}

.editor-content hr {
    border: none;
    height: 2px;
    background: linear-gradient(90deg, transparent, #dee2e6, transparent);
    margin: 20px 0;
}







/* Dropdown items */
.dropdown-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #333;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item:last-child {
    border-bottom: none;
}



.highlight-box {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 16px;
    margin: 16px 0;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
}

.quote-block {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: 1px solid #2196f3;
    border-radius: 8px;
    padding: 16px;
    margin: 16px 0;
    position: relative;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
}

.quote-block:before {
    content: '"';
    font-size: 3em;
    color: #2196f3;
    position: absolute;
    top: -10px;
    left: 10px;
    font-family: serif;
}

@media (max-width: 768px) {
    .editor-toolbar {
        padding: 8px 12px;
        gap: 4px;
    }
    
    .toolbar-btn {
        padding: 6px 8px;
        min-width: 32px;
        height: 32px;
        font-size: 12px;
    }
    
    .toolbar-select {
        min-width: 120px;
        font-size: 12px;
        padding: 6px 8px;
    }
    
    .editor-content {
        padding: 16px;
        font-size: 15px;
    }
}
</style>

<script>
class RichTextEditor {
    constructor() {
        this.editor = document.getElementById('rich-content-editor');
        this.textarea = document.getElementById('content-textarea');
        this.wordCounter = document.getElementById('word-counter');
        this.currentRange = null;
        
        this.init();
    }
    
    init() {
        // Initialize toolbar buttons
        this.initToolbarButtons();
        
        // Initialize dropdowns
        this.initDropdowns();
        
        // Sync content with textarea
        this.initContentSync();
        
        // Initialize word count
        this.updateWordCount();
        
        // Initialize keyboard shortcuts
        this.initKeyboardShortcuts();
        
        // Initialize auto-save (optional)
        this.initAutoSave();
    }
    
    initToolbarButtons() {
        const toolbarBtns = document.querySelectorAll('.toolbar-btn[data-command]');
        const formatSelect = document.querySelector('.toolbar-select[data-command]');
        
        toolbarBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const command = btn.dataset.command;
                
                if (command === 'formatBlock') {
                    return; // Handled by select
                }
                
                this.saveSelection();
                this.execCommand(command);
                this.updateToolbarState();
                this.syncContent();
                this.editor.focus();
            });
        });
        
        if (formatSelect) {
            formatSelect.addEventListener('change', (e) => {
                const value = e.target.value;
                this.saveSelection();
                if (value) {
                    this.execCommand('formatBlock', value);
                } else {
                    this.execCommand('removeFormat');
                }
                this.updateToolbarState();
                this.syncContent();
                this.editor.focus();
            });
        }
    }
    
    initDropdowns() {
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                const dropdownId = toggle.dataset.toggle + '-dropdown';
                const dropdown = document.getElementById(dropdownId);
                
                // Close other dropdowns
                document.querySelectorAll('.toolbar-dropdown').forEach(d => {
                    if (d.id !== dropdownId) d.classList.remove('show');
                });
                
                dropdown.classList.toggle('show');
            });
        });
        

        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.toolbar-group')) {
                document.querySelectorAll('.toolbar-dropdown').forEach(d => {
                    d.classList.remove('show');
                });
            }
        });
    }
    
    initContentSync() {
        this.editor.addEventListener('input', () => {
            this.syncContent();
            this.updateWordCount();
            this.updateToolbarState();
        });
        
        this.editor.addEventListener('keyup', () => {
            this.updateToolbarState();
        });
        
        this.editor.addEventListener('mouseup', () => {
            this.updateToolbarState();
        });
        
        // Load initial content if textarea has value
        if (this.textarea.value) {
            this.editor.innerHTML = this.textarea.value;
            this.updateWordCount();
        }
    }
    
    initKeyboardShortcuts() {
        this.editor.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key.toLowerCase()) {
                    case 'b':
                        e.preventDefault();
                        this.execCommand('bold');
                        break;
                    case 'i':
                        e.preventDefault();
                        this.execCommand('italic');
                        break;
                    case 'u':
                        e.preventDefault();
                        this.execCommand('underline');
                        break;
                    case 'z':
                        if (e.shiftKey) {
                            e.preventDefault();
                            this.execCommand('redo');
                        } else {
                            e.preventDefault();
                            this.execCommand('undo');
                        }
                        break;
                    case 'y':
                        e.preventDefault();
                        this.execCommand('redo');
                        break;
                }
                this.updateToolbarState();
                this.syncContent();
            }
        });
    }
    
    initAutoSave() {
        setInterval(() => {
            this.syncContent();
            // Could implement localStorage backup here
        }, 30000); // Auto-save every 30 seconds
    }
    
    execCommand(command, value = null) {
        this.restoreSelection();
        document.execCommand(command, false, value);
        this.saveSelection();
    }
    
    saveSelection() {
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            this.currentRange = selection.getRangeAt(0);
        }
    }
    
    restoreSelection() {
        if (this.currentRange) {
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(this.currentRange);
        }
    }
    
    syncContent() {
        this.textarea.value = this.editor.innerHTML;
    }
    
    updateWordCount() {
        const text = this.editor.textContent || '';
        const words = text.trim() ? text.trim().split(/\s+/).length : 0;
        this.wordCounter.textContent = words;
    }
    
    updateToolbarState() {
        const commands = ['bold', 'italic', 'underline', 'strikeThrough'];
        
        commands.forEach(command => {
            const btn = document.querySelector(`.toolbar-btn[data-command="${command}"]`);
            if (btn) {
                if (document.queryCommandState(command)) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            }
        });
        
        // Update format select
        const formatSelect = document.querySelector('.toolbar-select[data-command="formatBlock"]');
        if (formatSelect) {
            const formatValue = document.queryCommandValue('formatBlock').toLowerCase();
            formatSelect.value = formatValue;
        }
    }
    

    

    

        // Add new alignment
        img.classList.add(alignment);
        this.syncContent();
    }
    

    

    


}

// Special insert functions
function insertQuoteBlock() {
    const editor = document.getElementById('rich-content-editor');
    const selection = window.getSelection();
    const text = selection.toString() || 'Enter your quote here...';
    
    const quoteHTML = `<div class="quote-block">${text}</div><br>`;
    
    if (selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        range.deleteContents();
        range.insertNode(document.createRange().createContextualFragment(quoteHTML));
    } else {
        editor.focus();
        document.execCommand('insertHTML', false, quoteHTML);
    }
    
    richTextEditor.syncContent();
    richTextEditor.updateWordCount();
}

function insertHighlightBox() {
    const editor = document.getElementById('rich-content-editor');
    const selection = window.getSelection();
    const text = selection.toString() || 'Important note or highlight...';
    
    const highlightHTML = `<div class="highlight-box">${text}</div><br>`;
    
    if (selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        range.deleteContents();
        range.insertNode(document.createRange().createContextualFragment(highlightHTML));
    } else {
        editor.focus();
        document.execCommand('insertHTML', false, highlightHTML);
    }
    
    richTextEditor.syncContent();
    richTextEditor.updateWordCount();
}

// Initialize rich text editor when DOM is loaded
let richTextEditor;
document.addEventListener('DOMContentLoaded', function() {
    richTextEditor = new RichTextEditor();
});
</script> 