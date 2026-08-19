import { Component, input, output, signal, computed, effect, ViewChild, ElementRef, inject } from '@angular/core';
import { MessageService } from '../../../core/services/message.service';

@Component({
  selector: 'app-autocomplete',
  templateUrl: './autocomplete.component.html',
})
export class AutocompleteComponent {
  protected msg = inject(MessageService);

  items = input<any[]>([]);
  displayField = input<string>('display_name');
  placeholder = input<string>('');
  loading = input(false);
  required = input(false);

  selectionChange = output<any>();

  @ViewChild('inputRef') inputRef!: ElementRef<HTMLInputElement>;

  filterText = signal('');
  selectedItem = signal<any>(null);
  isOpen = signal(false);
  activeIndex = signal(-1);

  filteredItems = computed(() => {
    if (this.selectedItem()) return [];
    const text = this.filterText().toLowerCase();
    if (!text) return this.items();
    return this.items().filter(item =>
      String(item[this.displayField()]).toLowerCase().includes(text)
    );
  });

  isInvalid = computed(() => {
    return this.filterText() !== '' && this.selectedItem() === null;
  });

  onInput(value: string): void {
    this.filterText.set(value);
    this.isOpen.set(true);
    this.activeIndex.set(-1);
    if (!value) {
      this.selectedItem.set(null);
      this.selectionChange.emit(null);
    }
  }

  selectItem(item: any): void {
    this.selectedItem.set(item);
    this.filterText.set(item[this.displayField()]);
    this.isOpen.set(false);
    this.activeIndex.set(-1);
    this.selectionChange.emit(item);
  }

  clearSelection(): void {
    this.selectedItem.set(null);
    this.filterText.set('');
    this.isOpen.set(false);
    this.selectionChange.emit(null);
    this.inputRef?.nativeElement?.focus();
  }

  onKeydown(event: KeyboardEvent): void {
    const items = this.filteredItems();
    if (!this.isOpen() || items.length === 0) return;

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        this.activeIndex.set(Math.min(this.activeIndex() + 1, items.length - 1));
        break;
      case 'ArrowUp':
        event.preventDefault();
        this.activeIndex.set(Math.max(this.activeIndex() - 1, 0));
        break;
      case 'Enter':
        event.preventDefault();
        if (this.activeIndex() >= 0 && this.activeIndex() < items.length) {
          this.selectItem(items[this.activeIndex()]);
        }
        break;
      case 'Escape':
        this.isOpen.set(false);
        this.activeIndex.set(-1);
        break;
    }
  }

  onBlur(): void {
    setTimeout(() => this.isOpen.set(false), 200);
  }

  onFocus(): void {
    if (!this.selectedItem()) {
      this.isOpen.set(true);
    }
  }

  trackById(_index: number, item: any): number {
    return item.id ?? _index;
  }
}
