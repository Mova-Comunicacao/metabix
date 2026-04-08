import { AfterViewInit, Component, ElementRef, Input, OnChanges, OnDestroy, OnInit, SimpleChanges, ViewChild } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { AiAssistantService, AiImagePreset, AiMessage, AiTextPreset } from '../../core';
// Settings
import { SettingsService } from '../../../../core/services';
import { BehaviorSubject, Subject, takeUntil } from 'rxjs';

@Component({
  selector: 'app-assistant-chat-inner',
  templateUrl: './assistant-chat-inner.component.html'
})
export class AssistantChatInnerComponent implements OnInit, AfterViewInit, OnChanges, OnDestroy {
  @Input() isDrawer: boolean = false;
  @Input() imagePreset: AiImagePreset | null = null;
  @Input() textPreset: AiTextPreset | null = null;
  //@HostBinding('class') class = 'card-body';
  //@HostBinding('id') id = this.isDrawer ? 'kt_drawer_chat_messenger_body' : 'kt_chat_messenger_body';
    
  @ViewChild('aiMessagesEnd') aiMessagesEnd!: ElementRef<HTMLDivElement>;
  @ViewChild('messageInput', { static: true }) messageInput: ElementRef<HTMLTextAreaElement>;

  private messages$ = new BehaviorSubject<AiMessage[]>([]);
  messagesObs = this.messages$.asObservable();

  formGroup: FormGroup;

  isLoading = false;
  
  isCopied: string | null = null;
  isError: string | null = null;

  currentLang: 'pt' | 'es' | 'en' = 'pt';

  currentTone: string = 'formal';
  currentMode: string = 'rewrite';

  currentImageUrl: string | null = null;

  // Getters
  get isLoading$() {
    return this.ai.isLoading$;
  }    
  get settings$() {
    return this.settings.settings$;
  }  

  private destroy$ = new Subject<void>();
  
  constructor(
    private fb: FormBuilder,
    // Services
    private settings: SettingsService,
    private ai: AiAssistantService,
  ) { 
    this.messagesObs = this.messages$.asObservable();
  }

  ngOnInit(): void {
    this.loadForm();
  }

  ngAfterViewInit() {
    this.messagesObs
    .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        setTimeout(() => {
          if (this.aiMessagesEnd?.nativeElement) {
            this.aiMessagesEnd.nativeElement.scrollIntoView({
              behavior: 'smooth',
              block: 'end',
            });
          }
      });
    });
  }  

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['textPreset'] && this.textPreset && this.formGroup) {
      const { 
        temperature, 
        max_tokens, 
        top_p, 
        stream, 
        json_mode, 
        moderation 
      } = this.textPreset;

      this.formGroup.patchValue({
          temperature: temperature,
          max_tokens: max_tokens,
          top_p: top_p,
          stream, 
          json_mode, 
          moderation           
        },
        { emitEvent: false } // avoid triggering valueChanges if you don't need it
      );
    }
  }
  
  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }  

  loadForm() {
    this.formGroup = this.fb.group({
      description: ['', Validators.compose([
        Validators.required, 
        Validators.minLength(3), 
      ])],
      temperature: [1.0],
      max_tokens: [1024],
      top_p: [0.95],
      stream: [false],
      json_mode: [false],
      moderation: [false],        
    });
  }

  copyMessage(message: AiMessage): void {
    if (!message?.text) return;

    // If message.text may contain HTML, you can strip tags:
    const textToCopy = this.stripHtml(message.text.toString());    

    this.isCopied = message.text;

    if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(textToCopy)
      .catch(() => {
        this.fallbackCopyText(textToCopy);
      });      
    } else {
      this.fallbackCopyText(textToCopy);
    }

    setTimeout(() => this.isCopied = null, 2000);
  }

  onTranslateContent() {
    const description = this.formGroup.value.description;
    if (!description) return;

    const userMessage: AiMessage = { type: 'in', text: description };
    this.addMessage(userMessage);

    this.isError = null;

    this.ai.translateText(description, this.currentLang, this.messages$.value).subscribe({
      next: (translated) => {
        if (translated) {
          const aiMessage: AiMessage = { type: 'out', text: translated, lang: this.currentLang };
          this.addMessage(aiMessage);
        } else {
          this.isError = 'Erro ao traduzir texto com IA.';
        }
      },
      error: (err) => {
        const apiMsg = err?.error?.alert?.message 
                    || err?.error?.data?.error 
                    || 'Falha na comunicação com o servidor.';
        this.isError = apiMsg;
      },
    });
  }

  downloadImage(message: AiMessage): void {
    if (!message?.text) return;

    // if text is a relative path like "uploads/ai/123.png"
    const downloadUrl = this.ai.getImageDownloadUrl(message.text, 'image/png');
    // simplest: just open in new tab (browser handles download)
    window.open(downloadUrl, '_blank');    
  }

  onAction(): void {
    if (this.formGroup.invalid) {
      this.formGroup.markAllAsTouched();
      return;
    }

    const { description, imgWidth, imgHeight } = this.formGroup.value;

    if (!description || !description.trim()) {
      this.isError = 'Digite um texto para usar com a IA.';
      return;
    }    

    this.isError = null;

    if (this.currentMode === 'rewrite') {
      this.onRewriteContent(description);
    } else {
      this.onGenerateImage(description, imgWidth || 1024, imgHeight || 1024);      
    }
  }

  private onGenerateImage(prompt: string, width: number, height: number): void {

    const userMessage: AiMessage = { type: 'in', text: prompt };
    this.addMessage(userMessage);

    this.isError = null;

    this.ai.generateImage(prompt, width, height)
    .subscribe({
      next: (url) => {
        if (url) {
          const aiMessage: AiMessage = {
            type: 'out',
            text: url,
            tone: this.currentTone,
            isImage: true,
          };
          this.addMessage(aiMessage);
        } else {
          this.isError = 'A resposta não retornou a URL da imagem.';
        }
      },
      error: (err) => {
        const apiMsg = err?.error?.alert?.message 
                    || err?.error?.data?.error 
                    || 'Falha na comunicação com o servidor.';
        this.isError = apiMsg;
      },
    });      
  }  

  private onRewriteContent(text: string): void {

    const userMessage: AiMessage = { type: 'in', text };
    this.addMessage(userMessage);

    const { temperature, max_tokens, top_p, stream, json_mode, moderation } = this.formGroup.value;

    const options = {
      temperature,
      max_tokens,
      top_p,
      stream,
      json_mode,
      moderation,
    };

    this.ai.rewriteText(text, this.currentTone, this.messages$.value, options)
    .subscribe({
      next: (rephrased) => {
        if (rephrased) {
          const aiMessage: AiMessage = { type: 'out', text: rephrased, tone: this.currentTone };
          this.addMessage(aiMessage);
        } else {
          this.isError = 'Erro ao processar texto com IA.';
        }
      },
      error: (err) => {
        const apiMsg = err?.error?.alert?.message 
                    || err?.error?.data?.error 
                    || 'Falha na comunicação com o servidor.';
        this.isError = apiMsg;
      },
    });
  }

  private fallbackCopyText(text: string): void {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
      document.execCommand('copy');
      // You could also set copiedId here if needed
    } catch (err) {
      console.error('Fallback: could not copy text', err);
    } finally {
      document.body.removeChild(textarea);
    }
  }

  // action
  setLang(lang: 'pt' | 'es' | 'en'): void {
    this.currentLang = lang;
  }  

  setTone(tone: string): void {
    this.currentTone = tone;
  }    

  setMode(mode: string): void {
    this.currentMode = mode;
  }  

  // form
  useMessageInForm(message: AiMessage): void {
    this.formGroup.patchValue({
      description: message.text,
    });

    // opcional: foca no textarea
    if (this.messageInput?.nativeElement) {
      this.messageInput.nativeElement.focus();
    }
  }

  continueFromMessage(message: AiMessage): void {
    const base = message.text;
    if (!base) return;

    this.isError = null;

    // adiciona a mensagem do usuário dizendo que quer continuar
    const userMessage: AiMessage = {
      type: 'in',
      text: 'Continue a partir do texto anterior.',
    };
    this.addMessage(userMessage);

    const history = this.messages$.value;

    this.ai.continueText(base, this.currentTone, this.currentLang, history).subscribe({
      next: (result) => {
        if (result) {
          const aiMessage: AiMessage = {
            type: 'out',
            text: result,
            tone: this.currentTone,
            lang: this.currentLang,
          };
          this.addMessage(aiMessage);
        } else {
          this.isError = 'Erro ao continuar texto com IA.';
        }
      },
      error: () => {
        this.isError = 'Falha na comunicação com o servidor.';
      },
    });
  }  

  addMessage(newMessage: AiMessage): void {
    const messages = [...this.messages$.value, newMessage];
    this.messages$.next(messages);
  }

  // styles
  getMessageCssClass(message: AiMessage): string {
    return `p-5 rounded text-dark fw-bold mw-lg-400px bg-light-${
      message.type === 'in' ? 'info' : 'primary'
    } text-${message.type === 'in' ? 'start' : 'end'}`;
  }

  /**
   * Optional helper: remove HTML tags before copying.
   * If you prefer to copy raw HTML, just skip this and use message.text directly.
   */
  private stripHtml(html: string): string {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
  }

  // helpers for View
  isControlValid(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.valid && (control.dirty || control.touched);
  }

  isControlInvalid(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.invalid && (control.dirty || control.touched);
  }

  controlHasError(validation: string, controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.hasError(validation) && (control.dirty || control.touched);
  }

  isControlTouched(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.dirty || control.touched;
  }   
}
