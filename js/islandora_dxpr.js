/**
 * @file
 * Initializes Islandora DXPR's opt-in Bootstrap interactions.
 */

(function (Drupal, once) {
  'use strict';

  const tooltipSelector = '[data-islandora-tooltip]';
  const headerSearchSelector = '.islandora-header-search';
  const facetFormSelector =
    'form[id^="views-exposed-form-solr-search-content-"]';
  const mobileFiltersSelector =
    '.main-container > .row > aside[data-islandora-sidebar="primary"]';
  const headerSearchCleanups = new WeakMap();
  const mobileFilterCleanups = new WeakMap();
  let pendingFacetFocus = null;

  const restoreFacetFocus = () => {
    if (!pendingFacetFocus) {
      return;
    }

    const pending = pendingFacetFocus;
    const form = document.getElementById(pending.formId);
    if (!form) {
      pendingFacetFocus = null;
      return;
    }

    let target;
    if (pending.type === 'chip') {
      target = Array.from(form.querySelectorAll(
        '.islandora-selected-filters__chip',
      )).find((chip) =>
        chip.dataset.filterName === pending.name &&
        chip.dataset.filterValue === pending.value,
      );
    }
    else {
      target = form.querySelector(
        '.form-actions input[type="submit"]:not([name="reset"]), ' +
        '.form-actions button[type="submit"]:not([name="reset"])',
      );
    }

    pendingFacetFocus = null;
    if (target) {
      target.focus();
    }
  };

  const scheduleFacetFocusRestore = () => {
    if (!window.jQuery) {
      window.setTimeout(restoreFacetFocus, 0);
      return;
    }

    window.jQuery(document)
      .off('ajaxStop.islandoraDxprFacetFocus')
      .one('ajaxStop.islandoraDxprFacetFocus', () => {
        window.requestAnimationFrame(restoreFacetFocus);
      });
  };

  Drupal.behaviors.islandoraDxprTooltips = {
    attach(context) {
      if (!window.bootstrap || !window.bootstrap.Tooltip) {
        return;
      }

      once('islandora-dxpr-tooltip', tooltipSelector, context).forEach(
        (element) => {
          const tooltip = window.bootstrap.Tooltip.getOrCreateInstance(element);
          element.addEventListener('click', () => tooltip.hide());
        },
      );
    },

    detach(context, _settings, trigger) {
      if (
        trigger !== 'unload' ||
        !window.bootstrap ||
        !window.bootstrap.Tooltip
      ) {
        return;
      }

      once
        .remove('islandora-dxpr-tooltip', tooltipSelector, context)
        .forEach((element) => {
          const tooltip = window.bootstrap.Tooltip.getInstance(element);
          if (tooltip) {
            tooltip.dispose();
          }
        });
    },
  };

  Drupal.behaviors.islandoraDxprHeaderSearch = {
    attach(context) {
      once('islandora-dxpr-header-search', headerSearchSelector, context)
        .forEach((form) => {
          const input = form.querySelector('.islandora-header-search__input');
          const submit = form.querySelector(
            '.islandora-header-search__submit',
          );
          if (!input || !submit) {
            return;
          }

          const isDesktop = () => document.body.classList.contains(
            'body--dxpr-theme-nav-desktop',
          );
          let expanded = false;

          const setExpanded = (nextExpanded, moveFocus = false) => {
            expanded = !isDesktop() || nextExpanded;
            form.classList.toggle('is-expanded', expanded);
            input.tabIndex = expanded ? 0 : -1;

            if (isDesktop()) {
              submit.setAttribute('aria-controls', input.id);
              submit.setAttribute('aria-expanded', String(expanded));
              submit.setAttribute(
                'aria-label',
                expanded
                  ? Drupal.t('Search the repository')
                  : Drupal.t('Open repository search'),
              );
            }
            else {
              submit.removeAttribute('aria-controls');
              submit.removeAttribute('aria-expanded');
              submit.setAttribute(
                'aria-label',
                Drupal.t('Search the repository'),
              );
            }

            if (moveFocus) {
              input.focus();
            }
          };

          const handleSubmit = (event) => {
            if (isDesktop() && !expanded) {
              event.preventDefault();
              setExpanded(true, true);
            }
          };
          const handleKeydown = (event) => {
            if (event.key === 'Escape' && isDesktop() && expanded) {
              event.preventDefault();
              setExpanded(false);
              submit.focus();
            }
          };
          const handleOutsidePointer = (event) => {
            if (
              isDesktop() &&
              expanded &&
              input.value === '' &&
              !form.contains(event.target)
            ) {
              setExpanded(false);
            }
          };
          const handleResize = () => {
            window.requestAnimationFrame(() => {
              setExpanded(!isDesktop() || form.matches(':focus-within'));
            });
          };

          form.classList.add('islandora-header-search--enhanced');
          form.addEventListener('submit', handleSubmit);
          form.addEventListener('keydown', handleKeydown);
          document.addEventListener('pointerdown', handleOutsidePointer);
          window.addEventListener('resize', handleResize);
          setExpanded(!isDesktop());

          headerSearchCleanups.set(form, () => {
            form.removeEventListener('submit', handleSubmit);
            form.removeEventListener('keydown', handleKeydown);
            document.removeEventListener('pointerdown', handleOutsidePointer);
            window.removeEventListener('resize', handleResize);
          });
        });
    },

    detach(context, _settings, trigger) {
      if (trigger !== 'unload') {
        return;
      }

      once
        .remove('islandora-dxpr-header-search', headerSearchSelector, context)
        .forEach((form) => {
          const cleanup = headerSearchCleanups.get(form);
          if (cleanup) {
            cleanup();
            headerSearchCleanups.delete(form);
          }
        });
    },
  };

  Drupal.behaviors.islandoraDxprSelectedFilters = {
    attach(context) {
      once('islandora-dxpr-selected-filters', facetFormSelector, context)
        .forEach((form) => {
          const summary = document.createElement('section');
          const heading = document.createElement('h3');
          const chips = document.createElement('div');
          const clear = document.createElement('button');
          const headingId = `${form.id}-selected-filters`;

          summary.className = 'islandora-selected-filters';
          summary.setAttribute('aria-labelledby', headingId);
          summary.setAttribute('aria-live', 'polite');
          heading.className = 'islandora-selected-filters__heading';
          heading.id = headingId;
          heading.textContent = Drupal.t('Selected filters');
          chips.className = 'islandora-selected-filters__chips';
          clear.className = 'islandora-selected-filters__clear';
          clear.type = 'button';
          clear.textContent = Drupal.t('Clear all');
          form.classList.add('islandora-selected-filters--enhanced');

          summary.append(heading, chips, clear);
          form.insertBefore(summary, form.firstElementChild);

          const checkboxes = () => Array.from(
            form.querySelectorAll('input[type="checkbox"]'),
          );
          const labelFor = (input) => Array.from(
            form.querySelectorAll('label[for]'),
          ).find((label) => label.htmlFor === input.id);
          const filterLabel = (input) => {
            const label = labelFor(input);
            const text = label ? label.textContent.trim() : input.value;
            return text.replace(/\s+\([\d,]+\)$/, '');
          };
          const submitButton = () => form.querySelector(
            '.form-actions input[type="submit"]:not([name="reset"]), ' +
            '.form-actions button[type="submit"]:not([name="reset"])',
          );
          const submit = () => {
            const button = submitButton();
            if (button) {
              button.click();
            }
          };
          const render = () => {
            const selected = checkboxes().filter((input) => input.checked);
            chips.replaceChildren();
            summary.hidden = selected.length === 0;

            selected.forEach((input) => {
              const label = filterLabel(input);
              const chip = document.createElement('button');
              const icon = document.createElement('span');

              chip.className = 'islandora-selected-filters__chip';
              chip.dataset.filterName = input.name;
              chip.dataset.filterValue = input.value;
              chip.type = 'button';
              chip.setAttribute(
                'aria-label',
                Drupal.t('Remove filter @label', { '@label': label }),
              );
              chip.append(document.createTextNode(label));
              icon.setAttribute('aria-hidden', 'true');
              icon.textContent = '×';
              chip.append(icon);
              chip.addEventListener('click', () => {
                const chipIndex = Array.from(chips.children).indexOf(chip);
                input.checked = false;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                const remainingChips = chips.querySelectorAll(
                  '.islandora-selected-filters__chip',
                );
                const focusTarget = remainingChips[
                  Math.min(chipIndex, remainingChips.length - 1)
                ] || submitButton();
                pendingFacetFocus = focusTarget && focusTarget.matches(
                  '.islandora-selected-filters__chip',
                )
                  ? {
                    type: 'chip',
                    formId: form.id,
                    name: focusTarget.dataset.filterName,
                    value: focusTarget.dataset.filterValue,
                  }
                  : { type: 'apply', formId: form.id };
                if (focusTarget) {
                  focusTarget.focus();
                }
                scheduleFacetFocusRestore();
                submit();
              });
              chips.append(chip);
            });
          };

          clear.addEventListener('click', () => {
            checkboxes().forEach((input) => {
              input.checked = false;
            });
            render();
            const button = submitButton();
            pendingFacetFocus = { type: 'apply', formId: form.id };
            if (button) {
              button.focus();
            }
            scheduleFacetFocusRestore();
            submit();
          });
          form.addEventListener('change', render);
          render();
        });
    },
  };

  Drupal.behaviors.islandoraDxprMobileFilters = {
    attach(context) {
      const sidebars = [
        ...(
          context instanceof Element && context.matches(mobileFiltersSelector)
            ? [context]
            : []
        ),
        ...context.querySelectorAll(mobileFiltersSelector),
      ].filter((aside) => aside.querySelector(facetFormSelector));

      once('islandora-dxpr-mobile-filters', sidebars)
        .forEach((aside) => {
          const media = window.matchMedia('(max-width: 61.999rem)');
          const wrapper = document.createElement('div');
          const toggle = document.createElement('button');
          let expanded = false;

          if (!aside.id) {
            aside.id = 'islandora-search-filters';
          }
          wrapper.className = 'islandora-mobile-filter-toggle-wrapper';
          toggle.className = 'islandora-mobile-filter-toggle';
          toggle.type = 'button';
          toggle.setAttribute('aria-controls', aside.id);
          wrapper.append(toggle);
          aside.parentNode.insertBefore(wrapper, aside);

          const selectedCount = () => aside.querySelectorAll(
            `${facetFormSelector} input[type="checkbox"]:checked`,
          ).length;
          const updateLabel = () => {
            if (expanded) {
              toggle.textContent = Drupal.t('Hide filters');
              return;
            }

            const count = selectedCount();
            toggle.textContent = count
              ? Drupal.formatPlural(
                count,
                'Show filters (1 selected)',
                'Show filters (@count selected)',
              )
              : Drupal.t('Show filters');
          };
          const render = () => {
            const mobile = media.matches;
            wrapper.hidden = !mobile;
            aside.hidden = mobile && !expanded;
            toggle.setAttribute('aria-expanded', String(mobile && expanded));
            updateLabel();
          };
          const handleToggle = () => {
            expanded = !expanded;
            render();
          };
          const handleKeydown = (event) => {
            if (event.key === 'Escape' && media.matches && expanded) {
              event.preventDefault();
              expanded = false;
              render();
              toggle.focus();
            }
          };
          const handleMediaChange = () => {
            expanded = false;
            render();
          };

          toggle.addEventListener('click', handleToggle);
          aside.addEventListener('keydown', handleKeydown);
          aside.addEventListener('change', updateLabel);
          media.addEventListener('change', handleMediaChange);
          render();

          mobileFilterCleanups.set(aside, () => {
            toggle.removeEventListener('click', handleToggle);
            aside.removeEventListener('keydown', handleKeydown);
            aside.removeEventListener('change', updateLabel);
            media.removeEventListener('change', handleMediaChange);
            aside.hidden = false;
            wrapper.remove();
          });
        });
    },

    detach(context, _settings, trigger) {
      if (trigger !== 'unload') {
        return;
      }

      once
        .remove('islandora-dxpr-mobile-filters', mobileFiltersSelector, context)
        .forEach((aside) => {
          const cleanup = mobileFilterCleanups.get(aside);
          if (cleanup) {
            cleanup();
            mobileFilterCleanups.delete(aside);
          }
        });
    },
  };
})(Drupal, once);
