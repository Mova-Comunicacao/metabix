import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-team-content',
  templateUrl: './team-content.component.html',
})
export class TeamContentComponent implements OnInit {

  tabs = {
    CARD_TAB: 0,
    TABLE_TAB: 1,
  };
  activeTabId: number | undefined | string;  
  
  constructor() { }
  
  ngOnInit(): void {
    this.loadTab();
  }
  
  loadTab(): any {
    //let tabActive = CookieComponent.get("tab_team_");
    const raw: any = localStorage.getItem("tab_team_");
    const tab = raw ? JSON.parse(raw)['tabId'] : 0;
    this.activeTabId = tab;    
  }

  setActiveTab(tabId: number) {
    const date = new Date(Date.now() + 2 * 24 * 60 * 60 * 1000); // +2 day from now
    const options = { expires: date }    
    //CookieComponent.set("tab_team_", tabId, options);
    const raw = JSON.stringify({
      tabId: tabId,
      options: options,
    });
    localStorage.setItem("tab_team_", raw)
    this.activeTabId = tabId;
  }

  getActiveTabCSSClass(tabId = 1) {
    if (tabId !== this.activeTabId) {
      return '';
    }

    return 'active';
  } 
}
