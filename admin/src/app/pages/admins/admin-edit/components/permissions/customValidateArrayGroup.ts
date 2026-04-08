import { AbstractControl, ValidationErrors } from "@angular/forms";

export function customValidateArrayGroup() {
  return function validate(formArr: AbstractControl): ValidationErrors | null {
    const filtered = formArr.value.filter((chk: any) => chk.feature);
    return filtered.length ? null : { hasError: true }
  };
}