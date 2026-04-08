import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, catchError, finalize, map, Observable, of, Subscription, tap } from 'rxjs';

import { environment } from '../../../../../environments/environment';

import { AiMessage, AiRequest, AiTextPreset } from '../models/assistant.model';

import { AlertService } from '../../../../core';
import { ApiModel } from '../../../../shared';

@Injectable({
  providedIn: 'root'
})
export class AiAssistantService {

    // Public fields
    public _isLoading$ = new BehaviorSubject<boolean>(false);
    public _subscriptions: Subscription[] = [];

    private _errorMessage = new BehaviorSubject<string>('');
        
    // Getters
    get isLoading$() {
        return this._isLoading$.asObservable();
    }
    get errorMessage$() {
        return this._errorMessage.asObservable();
    }  
    get subscriptions() {
        return this._subscriptions;
    }

    protected http: HttpClient;
    // API URL has to be overrided
    API_URL = `${environment.apiUrl}/assistant`;
    constructor(http: HttpClient, private alert: AlertService) {
        this.http = http;
    }

    process(payload: AiRequest): Observable<ApiModel> {
        this._isLoading$.next(true);
        this._errorMessage.next('');  
        
        const url = `${this.API_URL}/process`;
        return this.http.post<ApiModel>(url, payload).pipe(
            tap(response => {
                if (response?.alert) {
                    this.alert?.toast(response.alert.type, response.alert.message);
                }
                console.log(response)
            }),  
            catchError(err => {
                this._isLoading$.next(false);
                this._errorMessage.next(err);  

                const alert = err?.error?.alert ?? {
                    type: 'error',
                    title: 'bad_request',
                    message: err?.message || 'Erro ao processar com IA.',
                };

                this.alert?.toast(alert.type, alert.message);
                console.error('ASSISTANT ERROR', err);

                return of({
                    ok: false,
                    alert,
                    data: undefined,
                } as ApiModel<{ output: string }>);
            }),          
            finalize(() => this._isLoading$.next(false))
        );
    }

    generate(prompt: string, width = 1024, height = 1024): Observable<ApiModel> {
        this._isLoading$.next(true);
        this._errorMessage.next('');  

        const url = `${this.API_URL}/generate`;
        const payload = { prompt, width, height };
        return this.http.post<ApiModel>(url, payload).pipe(
            tap(response => {
                if (response?.alert) {
                    this.alert?.toast(response.alert.type, response.alert.message);
                }
            }),  
            catchError(err => {
                this._isLoading$.next(false);
                this._errorMessage.next(err);  

                const alert = err?.error?.alert ?? {
                    type: 'error',
                    title: 'bad_request',
                    message: err?.message || 'Falha ao gerar imagem.',
                };

                this.alert?.toast(alert.type, alert.message);
                console.error('ASSISTANT ERROR', err);

                return of({
                    ok: false,
                    alert,
                    data: undefined,
                } as ApiModel<{ output: string }>);
            }),          
            finalize(() => this._isLoading$.next(false))
        );
    }  
    
    getImageDownloadUrl(path: string, mime: string = 'image/png'): string {
        const url = `${this.API_URL}/preview_image`;
        return `${this.API_URL}/preview_image?path=${encodeURIComponent(path)}`;
    }

    generateImage(prompt: string, width = 1024, height = 1024): Observable<string | null> {
        return this.generate(prompt, width, height).pipe(
            map(res => res.ok && res.data ? res.data.output : null)
        );
    }

    rewriteText(text: string, tone: AiRequest['tone'] = 'neutro', history: AiMessage[] = [], options: AiTextPreset): Observable<string | null> {
        return this.process({ task: 'rewrite', text, tone, history, options }).pipe(
            map(res => res.ok && res.data ? res.data.output : null)
        );
    }

    translateText(text: string, targetLang: string = 'pt', history: AiMessage[] = []): Observable<string | null> {
        return this.process({ task: 'translate', text, targetLang, history }).pipe(
            map(res => res.ok && res.data ? res.data.output : null)
        );
    }

    generateSeo(text: string): Observable<string | null> {
        return this.process({ task: 'seo', text }).pipe(
            map(res => res.ok && res.data ? res.data.output : null)
        );
    }

    generateText(instruction: string): Observable<string | null> {
        return this.process({ task: 'generate', text: instruction }).pipe(
            map(res => res.ok && res.data ? res.data.output : null)
        );
    }

    continueText(text: string, tone: AiRequest['tone'] = 'neutro', targetLang: string = 'pt', history: AiMessage[] = []): Observable<string | null> {
        return this.process({ task: 'continue', text, tone, targetLang, history }).pipe(
            map(res => res.ok && res.data ? res.data.output : null)
        );
    }    
    
}
