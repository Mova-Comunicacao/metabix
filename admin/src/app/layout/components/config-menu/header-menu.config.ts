export const HeaderMenuConfig = {
  items: [
    {
      title: 'Dashboards',
      root: true,
      alignment: 'left',
      page: '/dashboard',
      translate: 'nav.dashboardNav',
    },

    {
      title: 'CRM',
      bullet: 'dot',
      page: '/crm',
      submenu: [
        {
          title: 'Clientes',
          translate: 'nav.clientsNav',
          svg: './assets/media/icons/duotune/general/gen049.svg', 
          page: '/crm/clients',    
          submenu: [
            {
              title: 'Clientes',
              translate: 'nav.clientsNav',
              page: '/crm/clients',                  
              bullet: 'dot', 
            },
            {
              title: 'Clientes',
              translate: 'nav.addClientNav',
              page: '/crm/clients/add',                  
              bullet: 'dot', 
            },            
          ]                        
        },
        {
          title: 'Contato',
          translate: 'nav.contactsNav',
          svg: './assets/media/icons/duotune/abstract/abs013.svg', 
          page: '/crm/contacts',                  
        },
        {
          title: 'Leads',
          translate: 'nav.leadsNav',
          svg: './assets/media/icons/duotune/general/gen002.svg', 
          page: '/crm/leads',                  
        }                
      ]
    },  
    {
      title: 'Redes Sociais',
      bullet: 'dot',
      page: '/social',
      translate: 'nav.socialNav',
      submenu: [
        {
          title: 'Redes Sociais',
          translate: 'nav.socialNav',
          svg: './assets/media/icons/duotune/social/soc011.svg',
          page: '/social',
        },
      ]
    },
  ]
};