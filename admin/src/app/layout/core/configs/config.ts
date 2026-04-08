export type LayoutType =
  | 'dark-sidebar'
  | 'light-sidebar'
  | 'dark-header'
  | 'light-header'
  | 'dark-drawer'
  | 'light-drawer';

export type CSSClassesType = Record<string, string[]>;

export type HTMLAttributesType = Record<string, Record<string, string | boolean>>;

export interface ILayoutComponent {
  componentName: string;
}

export interface IPageLoader extends ILayoutComponent {
  componentName: 'page-loader';
  type?: 'none' | 'default' | 'spinner-message' | 'spinner-logo';
  logoImage?: string;
  logoClass?: string;
}

export interface IScrollTop extends ILayoutComponent {
  display?: boolean;
}

export interface IHeader extends ILayoutComponent {
  componentName: 'header';
  display?: boolean;
  default?: {
    container?: 'fluid' | 'fixed';
    containerClass?: string;
    fixed?: {
      desktop?: boolean;
      mobile?: boolean;
    };
    content?: string;
    menu?: {
      display?: boolean;
      iconType?: 'svg' | 'font';
    };
  };
}

export interface IDrawer extends ILayoutComponent {
  componentName: 'drawer';
  display?: boolean;
  mode?: 'light' | 'dark' | 'system';
}

export interface ISidebar extends ILayoutComponent {
  componentName: 'sidebar';
  display?: boolean;
  default?: {
    class?: string;
    push?: {
      header?: boolean;
      toolbar?: boolean;
      footer?: boolean;
    };
    drawer?: {
      enabled?: boolean;
      attributes?: Record<string, string>;
    };
    sticky?: {
      enabled?: boolean;
      attributes?: Record<string, string>;
    };
    fixed?: {
      desktop?: boolean;
    };
    minimize?: {
      desktop?: {
        enabled?: boolean;
        default?: boolean;
        hoverable?: boolean;
      };
    };
    menu?: {
      iconType?: 'svg' | 'font';
    };
  };
  toggle?: boolean;
}

export interface IToolbar extends ILayoutComponent {
  componentName: 'toolbar';
  display?: boolean;
  layout?: 'classic' | 'accounting' | 'extended' | 'reports' | 'saas';
  class?: string;
  container?: 'fixed' | 'fluid';
  containerClass?: string;
  fixed?: {
    desktop?: boolean;
    mobile?: boolean;
  };

  // Custom settings
  filterButton?: boolean;
  daterangepickerButton?: boolean;
  primaryButton?: boolean;
  primaryButtonLabel?: string;
  primaryButtonModal?: string;
}

export interface IMain extends ILayoutComponent {
  type?: 'blank' | 'default' | 'none'; // Set layout type: default|blank|none
  pageBgWhite?: boolean; // Set true if page background color is white
}

export interface IIllustrations extends ILayoutComponent {
  componentName: 'illustrations';
  set?: 'sketchy-1';
}

export interface IGeneral extends ILayoutComponent {
  componentName: 'general';
  evolution?: boolean;
  layoutType?: 'default' | 'blank';
  mode?: 'light' | 'dark' | 'system';
  rtl?: boolean;
  primaryColor?: string; // Used in email templates
  pageBgWhite?: boolean; // Set true if page background color is white
  pageWidth?: 'default' | 'fluid' | 'fixed';
}

export interface IMegaMenu extends ILayoutComponent {
  display: boolean;
}

export interface ISidebarPanel extends ILayoutComponent {
  componentName: 'sidebar-panel';
  display: boolean;
}

export interface IContent extends ILayoutComponent {
  componentName: 'content';
  container?: 'fixed' | 'fluid';
}

export interface IFooter extends ILayoutComponent {
  componentName: 'footer';
  display?: boolean;
  container?: 'fluid' | 'fixed';
  containerClass?: string;
  placement?: string;
  fixed?: {
    desktop?: boolean;
    mobile?: boolean;
  };
}

export interface IPageTitle extends ILayoutComponent {
  componentName: 'page-title';
  display?: boolean;
  breadCrumb?: boolean;
  description?: boolean;
  direction?: 'row' | 'column';
  class?: string;
}

export interface IEngage extends ILayoutComponent {
  componentName: 'engage';
  demos?: {
    enabled?: boolean;
  };
  purchase?: {
    enabled?: boolean;
  };
}

export interface IApp {
  general?: IGeneral;
  header?: IHeader;
  sidebar?: ISidebar;
  sidebarPanel?: ISidebarPanel;
  toolbar?: IToolbar;
  pageTitle?: IPageTitle;
  content?: IContent;
  footer?: IFooter;
  pageLoader?: IPageLoader;
  drawer?: IDrawer;
}

export interface ILayout {
  main?: IMain;
  app?: IApp;
  illustrations?: IIllustrations;
  scrolltop?: IScrollTop;
  engage?: IEngage;
}

export interface ILayoutCSSClasses {
  header: string[];
  headerContainer: string[];
  headerMobile: string[];
  headerMenu: string[];
  aside: string[];
  asideMenu: string[];
  asideToggle: string[];
  sidebar: string[];
  toolbar: string[];
  toolbarContainer: string[];
  content: string[];
  contentContainer: string[];
  footerContainer: string[];
  pageTitle: string[];
  pageContainer: string[];
  drawer: string[];
}

export interface ILayoutHTMLAttributes {
  asideMenu: Map<string, string | number | boolean>;
  headerMobile: Map<string, string | number | boolean>;
  headerMenu: Map<string, string | number | boolean>;
  headerContainer: Map<string, string | number | boolean>;
  pageTitle: Map<string, string | number | boolean>;
}
