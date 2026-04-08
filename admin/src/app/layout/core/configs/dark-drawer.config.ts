import { ILayout } from './config';

export const DarkDrawerConfig: ILayout = {
  main: {
    componentName: 'drawer',
    type: 'default',
    pageBgWhite: false,  
  },
  app: {
    general: {
      componentName: 'general',
      evolution: true,
      layoutType: 'default',
      mode: 'light',
      rtl: false,
      primaryColor: '#50CD89',
      pageBgWhite: false,
      pageWidth: 'default',
    },      
  }    
}