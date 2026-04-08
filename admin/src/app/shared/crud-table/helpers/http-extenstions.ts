import { ITableState } from '..';

export function baseFilter(entities: any[], requestObj: ITableState) {
  // Filtration
  let entitiesResult = filterArray(entities, requestObj);
  // Search
  entitiesResult = searchInArray(entitiesResult, requestObj.search_string);

  // Sorting
  // start
  if (requestObj.sorting.column) {
    entitiesResult = sortArray(entitiesResult, requestObj.sorting.column, requestObj.sorting.direction);
  }
  // end

  // Paginator
  // start
  const totalCount = entitiesResult.length;
  const startPosition = (requestObj.paginator.page - 1) * requestObj.paginator.pageSize;
  const endPosition = startPosition + requestObj.paginator.pageSize;
  entitiesResult = entitiesResult.slice(startPosition, endPosition);
  // end

  const responseObj = {
    items: entitiesResult,
    total: totalCount
  };
  return responseObj;
}

export function sortArray(incomingArray: any[], sortField = '', sortOrder = 'asc'): any[] {
  if (!sortField) {
    return incomingArray;
  }

  let result: any[] = [];
  result = incomingArray.sort((a, b) => {
    if (a[sortField] < b[sortField]) {
      return sortOrder === 'asc' ? -1 : 1;
    }

    if (a[sortField] > b[sortField]) {
      return sortOrder === 'asc' ? 1 : -1;
    }

    return 0;
  });
  return result;
}

export function filterArray(incomingArray: any[], requestObj: ITableState): any[] {
  if (!requestObj || !requestObj.filter) {
    return incomingArray;
  }

  let result: any[] = incomingArray;
  const filtrationFields = Object.keys(requestObj.filter);
  filtrationFields.forEach((keyName: string) => {
       result = result.filter(el => el[keyName] == requestObj.filter[keyName]);
  });
  return result;
}

export function searchInArray(incomingArray: any[], search_string = ''): any[] {
  if (!search_string) {
    return incomingArray;
  }

  const acceptableForSearchTypes: any = { number: 1, string: 2 };
  const result = incomingArray.filter(row => {
    let termIsFoundInRow = false;
    const keys = Object.keys(row).filter(keyName => acceptableForSearchTypes[typeof row[keyName]] && row[keyName]);
    for (const keyName of keys) {
      if (row[keyName].toString().toLowerCase().indexOf(search_string) > -1) {
        termIsFoundInRow = true;
        break;
      }
    }
    return termIsFoundInRow;
  });
  return result;
}
