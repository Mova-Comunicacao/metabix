import { Injectable, Inject } from '@angular/core';

import { environment } from '../../../../../environments/environment';

import { AiChangelogItem } from '../models/assistant-log.model';

@Injectable({
  providedIn: 'root'
})
export class AssistantLogService {

    aiAbout = `
    movaAI beta é o assistente de conteúdo integrado ao painel.
    Ele ajuda a reescrever textos em português, traduzir para inglês/espanhol.
  `;

  aiChangelog: AiChangelogItem[] = [
    {
      version: 'beta',
      label: 'beta',
      date: '2025-11-28',
      items: [
        'Reescrita de textos em português com ajuste de tom.',
        'Tradução de conteúdo de PT para EN e ES.',
        'Histórico de mensagens em formato de chat.',
        'Limite mensal global de uso da IA com logging de requisições.',
      ],
    },
    // {
    //   version: '1.0',
    //   label: 'stable',
    //   date: '2026-02-10',
    //   items: [
    //     'Melhorias na qualidade de tradução.',
    //     'Novo modo de geração de ALT text para imagens.',
    //   ],
    // },    
  ];  
}