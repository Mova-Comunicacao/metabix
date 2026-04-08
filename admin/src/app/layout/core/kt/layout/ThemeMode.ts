import {EventHandlerUtil} from '../_utils'

type Mode = 'light' | 'dark' | 'system'

class ThemeMode {
  menu: HTMLElement | null = null
  element: HTMLElement | null = null

  private getParamName = (postfix: string): string => {
    const ktName = document.body.hasAttribute('data-kt-name')
    const name = ktName ? ktName + '_' : ''
    return 'kt_' + name + 'theme_mode_' + postfix
  }

  public getMode = (): Mode => {
    const modeParam: string = this.getParamName('value')
    const menuMode: Mode | '' = this.getMenuMode()
    const drawerMode: Mode | '' = this.getDrawerMode()
    const defaultMode = 'light'
    if (!localStorage) {
      return defaultMode
    }

    const ls = localStorage.getItem(modeParam)
    if (ls) {
      return ls as Mode
    }

    const dataTheme = this.element?.getAttribute('data-bs-theme')
    if (dataTheme) {
      return dataTheme as Mode
    }

    if (!menuMode) {
      return defaultMode
    }

    if (menuMode === 'system' && drawerMode === 'system') {
      return this.getSystemMode()
    }

    return menuMode
  }

  public setMode = (mode: Mode, menuMode: Mode | '', drawerMode?: Mode | ''): void => {
    // Check input values
    if (mode !== 'light' && mode !== 'dark') {
      return
    }

    // Get param names
    const modeParam: string = this.getParamName('value')
    const menuModeParam: string = this.getParamName('menu')
    const drawerModeParam: string = this.getParamName('drawer')

    // Reset mode if system mode was changed
    if (menuMode === 'system') {
      if (this.getSystemMode() !== mode) {
        mode = this.getSystemMode()
      }
    }

    // Check menu mode
    if (!menuMode) {
      menuMode = mode
    }


    // Reset mode if system mode was changed
    if (drawerMode === 'system') {
      if (this.getSystemMode() !== mode) {
        mode = this.getSystemMode()
      }
    }

    // Check menu mode
    if (!drawerMode) {
      drawerMode = mode
    }    

    // Read active drawer mode value
    const activeDrawer: HTMLElement | null =
      this.menu?.querySelector('[data-kt-element="mode"][data-kt-value="' + menuMode + '"]') || null

    // Read active menu mode value
    const activeMenuItem: HTMLElement | null =
      this.menu?.querySelector('[data-kt-element="mode"][data-kt-value="' + menuMode + '"]') || null

    // Enable switching state
		this.element?.setAttribute("data-kt-theme-mode-switching", "true");

    // Set mode to the target element
    this.element?.setAttribute('data-bs-theme', mode);

    // Disable switching state
    const self = this;
		setTimeout(function() {
			self.element?.removeAttribute("data-kt-theme-mode-switching");
		}, 300);

    // Store mode value in storage
    if (localStorage) {
      localStorage.setItem(modeParam, mode)
    }

    // Set active menu item
    if (activeMenuItem && localStorage) {
      localStorage.setItem(menuModeParam, menuMode);
      this.setActiveMenuItem(activeMenuItem);
    }

    // Set active drawer
    if (activeDrawer && localStorage) {
      localStorage.setItem(drawerModeParam, drawerMode);
      this.setActiveDrawer(activeDrawer);
    }    

    // Flip images
    this.flipImages()
  }

  public getMenuMode = (): Mode | '' => {
    const menuModeParam = this.getParamName('menu')
    const menuItem = this.menu?.querySelector('.active[data-kt-element="mode"]')
    const dataKTValue = menuItem?.getAttribute('data-kt-value')
    if (dataKTValue) {
      return dataKTValue as Mode
    }

    if (!menuModeParam) {
      return ''
    }

    const ls = localStorage ? localStorage.getItem(menuModeParam) : null
    return (ls as Mode) || ''
  }

  public getDrawerMode = (): Mode | '' => {
    const drawerModeParam = this.getParamName('drawer')
    const drawer = this.menu?.querySelector('.active[data-kt-element="mode"]')
    const dataKTValue = drawer?.getAttribute('data-kt-value')
    if (dataKTValue) {
      return dataKTValue as Mode
    }

    if (!drawerModeParam) {
      return ''
    }

    const ls = localStorage ? localStorage.getItem(drawerModeParam) : null
    return (ls as Mode) || ''
  }  

  public getSystemMode = (): Mode => {
    return window.matchMedia('(prefers-color-scheme: dark)') ? 'dark' : 'light'
  }

  private initMode = (): void => {
    this.setMode(this.getMode(), this.getMenuMode(), this.getDrawerMode())
    if (this.element) {
      EventHandlerUtil.trigger(this.element, 'kt.thememode.init')
    }
  }

  private getActiveMenuItem = (): HTMLElement | null => {
    return (
      this.menu?.querySelector(
        '[data-kt-element="mode"][data-kt-value="' + this.getMenuMode() + '"]'
      ) || null
    )
  }

  private setActiveMenuItem = (item: HTMLElement): void => {
    const menuModeParam = this.getParamName('menu')
    const menuMode = item.getAttribute('data-kt-value')
    const activeItem = this.menu?.querySelector('.active[data-kt-element="mode"]')
    if (activeItem) {
      activeItem.classList.remove('active')
    }

    item.classList.add('active')
    if (localStorage && menuMode && menuModeParam) {
      localStorage.setItem(menuModeParam, menuMode)
    }
  }

  private setActiveDrawer = (item: HTMLElement): void => {
    const drawerModeParam = this.getParamName('drawer')
    const drawerMode = item.getAttribute('data-kt-value')
    const activeItem = this.menu?.querySelector('.active[data-kt-element="mode"]')
    if (activeItem) {
      activeItem.classList.remove('active')
    }

    if (localStorage && drawerMode && drawerModeParam) {
      localStorage.setItem(drawerModeParam, drawerMode)
    }
  }  

  private handleMenu = (): void => {
    this.menu
      ?.querySelectorAll<HTMLElement>('[data-kt-element="mode"]')
      ?.forEach((item: HTMLElement) => {
        item.addEventListener('click', (e) => {
          e.preventDefault()

          const menuMode: string | null = item.getAttribute('data-kt-value')
          const mode = menuMode === 'system' ? this.getSystemMode() : menuMode

          if (mode) {
            this.setMode(mode as Mode, menuMode as Mode | '')
          }
        })
      })
  }

  public flipImages = () => {
    document.querySelectorAll<HTMLElement>('[data-kt-img-dark]')?.forEach((item: HTMLElement) => {
      if (item.tagName === 'IMG') {
        if (this.getMode() === 'dark' && item.hasAttribute('data-kt-img-dark')) {
          item.setAttribute('data-kt-img-light', item.getAttribute('src') || '')
          item.setAttribute('src', item.getAttribute('data-kt-img-dark') || '')
        } else if (this.getMode() === 'light' && item.hasAttribute('data-kt-img-light')) {
          item.setAttribute('data-kt-img-dark', item.getAttribute('src') || '')
          item.setAttribute('src', item.getAttribute('data-kt-img-light') || '')
        }
      } else {
        if (this.getMode() === 'dark' && item.hasAttribute('data-kt-img-dark')) {
          item.setAttribute('data-kt-img-light', item.getAttribute('src') || '')
          item.style.backgroundImage = "url('" + item.getAttribute('data-kt-img-dark') + "')"
        } else if (this.getMode() === 'light' && item.hasAttribute('data-kt-img-light')) {
          item.setAttribute('data-kt-img-dark', item.getAttribute('src') || '')
          item.style.backgroundImage = "url('" + item.getAttribute('data-kt-img-light') + "')"
        }
      }
    })
  }

  public on = (name: string, hander: Function) => {
    if (this.element) {
      return EventHandlerUtil.on(this.element, name, hander)
    }
  }

  public off = (name: string, handlerId: string) => {
    if (this.element) {
      return EventHandlerUtil.off(this.element, name, handlerId)
    }
  }

  public init = () => {
    this.menu = document.querySelector<HTMLElement>('[data-kt-element="theme-mode-menu"]')
    this.element = document.documentElement

    this.initMode()

    if (this.menu) {
      this.handleMenu()
    }
  }
}

const ThemeModeComponent = new ThemeMode()
// Initialize app on document ready => ThemeModeComponent.init()
export {ThemeModeComponent}
