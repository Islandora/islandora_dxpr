/**
 * @file
 * Initializes Islandora DXPR's opt-in Bootstrap interactions.
 */

(function (Drupal, once) {
  'use strict';

  const tooltipSelector = '[data-islandora-tooltip]';
  const headerSearchSelector = '.islandora-header-search';
  const headerSearchCleanups = new WeakMap();

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
})(Drupal, once);
