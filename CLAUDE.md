# Project Coding Standards

## JavaScript — Always use jQuery

jQuery is loaded on **every page** in this project. Always write jQuery. Never write vanilla JS unless jQuery genuinely cannot do it (e.g. `new Razorpay(...)`, `new ResizeObserver(...)`, canvas/WebGL APIs).

### Mandatory jQuery equivalents

| Vanilla JS | jQuery |
|---|---|
| `document.getElementById('x')` | `$('#x')` |
| `document.querySelector('.x')` | `$('.x').first()` |
| `document.querySelectorAll('.x').forEach(fn)` | `$('.x').each(fn)` |
| `el.addEventListener('click', fn)` | `$('#x').on('click', fn)` |
| `document.addEventListener('DOMContentLoaded', fn)` | `$(fn)` |
| `el.classList.add/remove/toggle` | `$(el).addClass/removeClass/toggleClass` |
| `el.textContent = x` | `$(el).text(x)` |
| `el.innerHTML = x` | `$(el).html(x)` |
| `el.disabled = true/false` | `$(el).prop('disabled', true/false)` |
| `el.dataset.x` | `$(el).data('x')` |
| `el.style.display = 'none'` | `$(el).hide()` |
| `el.style.display = 'flex'` | `$(el).css('display', 'flex')` |
| `fetch(url, {method:'POST'}).then().catch()` | `$.ajax({url, method:'POST', data, dataType:'json', success, error})` |
| `fetch(...).finally(fn)` | `$.ajax(...).always(fn)` |

### When fixing existing files

If you encounter vanilla JS (`document.getElementById`, `fetch`, `addEventListener`, `classList`, `querySelectorAll`, etc.) anywhere in a file you are already editing, convert it to jQuery in the same pass. Do not leave a mix of vanilla JS and jQuery in the same file.

---

## PHP

- All SELECT queries → `$this->ReadDb`
- All INSERT / UPDATE / DELETE → `$this->dbwrite_model`
- Every function must have typed parameters + return type declaration.

## JS / jQuery functions

- Every function must have a JSDoc `@param` + `@returns` block.

## Comments

- Single thought on one line → `//`
- Multi-line explanation → `/* */`
- Never stack multiple `//` lines for a single thought.

## Commits

- Never run `git commit` without explicit user instruction.
