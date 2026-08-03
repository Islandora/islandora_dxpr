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
  const resultsPerPageSelector = '[data-islandora-results-per-page]';
  const headerSearchCleanups = new WeakMap();
  const mobileFilterCleanups = new WeakMap();
  let pendingResultsPerPageFocus = false;

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

  Drupal.behaviors.islandoraDxprResultsPerPage = {
    attach(context) {
      once(
        'islandora-dxpr-results-per-page',
        resultsPerPageSelector,
        context,
      ).forEach((select) => {
        let pageSizeUrls = {};
        try {
          pageSizeUrls = JSON.parse(
            select.dataset.islandoraResultsPerPageUrls || '{}',
          );
        }
        catch (_error) {
          return;
        }
        const control = select.closest('.pager__results--dropdown');
        if (!control) {
          return;
        }

        select.disabled = false;
        control.classList.add('is-enhanced');

        if (pendingResultsPerPageFocus) {
          pendingResultsPerPageFocus = false;
          window.requestAnimationFrame(() => select.focus());
        }

        select.addEventListener('change', () => {
          if (select.value === '') {
            return;
          }

          const destination = pageSizeUrls[select.value];
          if (!destination) {
            return;
          }
          const href = new URL(destination, window.location.origin);

          if (window.historyInitiated === true) {
            pendingResultsPerPageFocus = true;
            window.history.pushState(null, document.title, href.toString());
          }
          else {
            window.location.assign(href.toString());
          }
        });
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

          const updateLabel = () => {
            toggle.textContent = expanded
              ? Drupal.t('Hide filters')
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
