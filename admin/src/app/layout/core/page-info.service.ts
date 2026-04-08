import { Injectable, Injector } from '@angular/core';
import { ActivatedRoute, ActivatedRouteSnapshot, NavigationCancel, NavigationEnd, Router } from '@angular/router';
import { BehaviorSubject, combineLatest, filter, map, startWith, Subject, takeUntil } from 'rxjs';

import { TranslateService } from '@ngx-translate/core';

export interface PageLink {
  title: string;
  path: string;
  breadcrumb?: string;
  isSeparator?: boolean;
  isActive?: boolean;
}

export class PageInfo {
  breadcrumbs: PageLink[] = [];
  title = '';
}

@Injectable({
  providedIn: 'root',
})
export class PageInfoService {
  public title$: BehaviorSubject<string> = new BehaviorSubject<string>('Dashboard');
  public description$: BehaviorSubject<string> = new BehaviorSubject<string>('');
  public breadcrumbs$: BehaviorSubject<PageLink[]> = new BehaviorSubject<PageLink[]>([]);

  private destroy$ = new Subject<void>();

  constructor(
    private readonly router: Router,
    private readonly route: ActivatedRoute,
    private readonly translate: TranslateService,
    private readonly injector: Injector // lazy para evitar ciclos com SeoService/Auth/etc    
  ) {
    this.init();  
  }

  public setTitle(_title: string) {
    this.title$.next(_title);
  }

  public setDescription(_description: string) {
    this.description$.next(_description);
  }

  public setBreadcrumbs(_bs: PageLink[]) {
    this.breadcrumbs$.next(_bs);
  }  

  private init() {
    const navEnd$ = this.router.events.pipe(
      filter(e => e instanceof NavigationEnd),
      startWith(null) // garante 1ª emissão (carregamento inicial)
    );

    const langChange$ = this.translate.onLangChange.pipe(
      startWith({ lang: this.translate.currentLang } as any) // garante 1ª emissão sem troca
    );

    // Atualiza título e breadcrumbs sempre que navega ou muda idioma
    combineLatest([navEnd$, langChange$])
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        const data = this.findDataInRouteTree(this.router.routerState.root);
        this.updateTitleFromData(data);
        this.updateBreadcrumbsFromData(data);
      });
  }  

  /** title */
  private updateTitleFromData(data: Record<string, any>) {
    const titleKey: string | undefined = data['translate'] || data['title'];

    if (titleKey) {
      this.translate.stream(titleKey).pipe(takeUntil(this.destroy$)).subscribe(label => {
        this.setTitle(label || titleKey || 'Dashboard');
      });
    } else {
      this.setTitle('Dashboard');
    }
  }

  private updateBreadcrumbsFromData(data: Record<string, any>) {
    const crumbs: PageLink[] = [];

    // Coleta todos os snapshots da raiz até o ativo (mais profundo)
    const snaps: ActivatedRouteSnapshot[] = [];
    let r: ActivatedRoute | null = this.router.routerState.root;
    while (r) {
      if (r.snapshot) snaps.push(r.snapshot);
      r = r.firstChild;
    }

    snaps.forEach((snap, idx) => {
      const data = snap.data || {};
      const key = data['breadcrumb'] || data['translate']; // || data['title'];
      if (!key) return;

      // Título traduzido (ou a chave, se não houver tradução)
      let title = this.translate.instant(key) || key;

      // Se for o último nível, tenta anexar o ID (ou outro parâmetro configurado)
      if (idx === snaps.length - 1) {
        const paramName = data['breadcrumbParam'] || 'id';
        const idValue = snap.paramMap?.get(paramName) || undefined;
        if (idValue) {
          title = `${title} #${idValue}`;
        }
      }

      // Monta o path acumulando segmentos desde a raiz
      const fullPath = snap.pathFromRoot
        .map(s => s.url.map(u => u.path).join('/'))
        .filter(Boolean)
        .join('/');

      crumbs.push({
        title,
        path: '/' + fullPath,
        breadcrumb: '',
        isSeparator: false,
        isActive: false,        
      });
      // add separator
      crumbs.push({
        title: '',
        path: '',
        breadcrumb: '',
        isSeparator: true,
        isActive: false,
      });      
    });

    if (crumbs.length) {
      //crumbs[crumbs.length - 1].isActive = false;
    }
    if (crumbs.length <= 0) {
      this.setBreadcrumbs([]);
      return;
    }    
    this.setBreadcrumbs(crumbs);
  }  

  /** router */
  private findDataInRouteTree(route: ActivatedRoute): Record<string, any> {
    let r: ActivatedRoute | null = route;
    let lastData: Record<string, any> = {};
    while (r) {
      if (r.snapshot?.data && Object.keys(r.snapshot.data).length > 0) {
        lastData = r.snapshot.data;
      }
      r = r.firstChild;
    }
    return lastData;
  }

  private getDeepestPrimaryRoute(route: ActivatedRoute): ActivatedRoute {
    while (route.firstChild) {
      route = route.firstChild;
    }
    return route.outlet === 'primary' ? route : this.route;
  }  

  private getRouteData(route: ActivatedRoute): Record<string, any> {
    let data = {};
    let current: ActivatedRoute | null = route;

    while (current) {
      data = { ...current.snapshot.data, ...data };
      current = current.parent;
    }

    return data;
  }  

  private maybeTranslate(keyOrText: string): string {
    if (!keyOrText) return '';
    const translated = this.translate.instant(keyOrText);
    return translated && translated !== keyOrText ? translated : keyOrText;
  }  

  destroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }  
}
