import { FormGroup } from '@angular/forms';

export interface ISearchView {
  searchGroup: FormGroup;
  ngOnInit(): void;
  searchForm(): void;
  search(search_string: string): void;
}
