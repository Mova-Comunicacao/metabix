import { Injectable } from '@angular/core';
import { Router, RouterStateSnapshot, TitleStrategy } from '@angular/router';
import { Title } from '@angular/platform-browser';

import { TranslateService } from '@ngx-translate/core';

@Injectable({
  providedIn: 'root'
})
export class I18nTitleStrategy extends TitleStrategy {
  private readonly SUFIXO = 'Admin'; // personalize

  constructor(
    private readonly router: Router,
    private readonly title: Title,
    private readonly translate: TranslateService
  ) {
    super();

    // quando o idioma muda, reaplica o título baseado no snapshot atual
    this.translate.onLangChange.subscribe(() => {
      this.updateTitle(this.router.routerState.snapshot);
    });
  }

  override updateTitle(snapshot: RouterStateSnapshot): void {
    const deepest = this.getDeepestWithData(snapshot.root);
    const data = deepest?.data || {};
    const key: string | undefined = data['translate'] || data['title'];

    const translated = key
      ? this.translate.instant(key)
      : '';

    const finalTitle = translated || key || 'Dashboard';
    this.title.setTitle(`${finalTitle} | ${this.SUFIXO}`);
  }

  private getDeepestWithData(node: any): any {
    let cur = node;
    let lastWithData = node;
    while (cur?.firstChild) {
      cur = cur.firstChild;
      if (cur?.data && Object.keys(cur.data).length) {
        lastWithData = cur;
      }
    }
    return lastWithData;
  }
  
}
