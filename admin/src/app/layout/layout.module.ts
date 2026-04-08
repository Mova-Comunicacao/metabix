import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';

// Components
import { HeaderComponent } from './components/header/header.component';
import { HeaderMenuComponent } from './components/header/header-menu/header-menu.component';
import { HeaderToolbarComponent } from './components/header-toolbar/toolbar.component';
import { PageTitleComponent } from './components/header-toolbar/page-title/page-title.component';

// Navbar
import { NavbarComponent } from './components/navbar/navbar.component';
import { UserNavbarComponent } from './components/navbar/user-navbar/user-navbar.component';
import { QuickLinksComponent } from './components/navbar/quick-links/quick-links.component';
import { LangNavbarComponent } from './components/navbar/lang-navbar/lang-navbar.component';

// Sidebar
import { SidebarComponent } from './components/sidebar/sidebar.component';
import { SidebarLogoComponent } from './components/sidebar/sidebar-logo/sidebar-logo.component';
import { SidebarMenuComponent } from './components/sidebar/sidebar-menu/sidebar-menu.component';
import { SidebarFooterComponent } from './components/sidebar/sidebar-footer/sidebar-footer.component';
// Footer
import { FooterComponent } from './components/footer/footer.component';
// Swicther
import { SwitcherComponent } from './components/switcher/switcher.component';

import { ContentComponent } from './components/content/content.component';
import { ScriptsInitComponent } from './components/scripts-init/scripts-init.component';

import { LayoutRoutingModule } from './layout-routing.module';
import { LayoutComponent } from './layout.component';

// Assistant
import {
  AssistantModule
} from '../modules/assistant';

import { 
  KeeniconModule,
  SharedModule
} from '../shared';

@NgModule({
  declarations: [
    LayoutComponent,
    // Header
    HeaderComponent,
    HeaderMenuComponent,   
    HeaderToolbarComponent,
    PageTitleComponent,
    // Navbar
    NavbarComponent,
    UserNavbarComponent,
    QuickLinksComponent,
    LangNavbarComponent,
    // Sidebar
    SidebarComponent, 
    SidebarLogoComponent,
    SidebarMenuComponent,
    SidebarFooterComponent,
    // Content
    ContentComponent,
    ScriptsInitComponent,
    // Footer
    FooterComponent,
    // Switcher
    SwitcherComponent,
  ],
  imports: [
    CommonModule,
    LayoutRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    // Plugins
    InlineSVGModule,
    KeeniconModule,
    // Assistant
    AssistantModule,
    // Shared
    SharedModule,
  ]
})
export class LayoutModule { }
