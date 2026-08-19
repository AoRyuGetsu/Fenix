import { Component, inject, computed, OnInit } from '@angular/core';
import { UsersFacade } from '../../facades/users.facade';
import { UsersTableComponent } from '../../components/users-table/users-table.component';
import { PersonCreateModalComponent } from '../../components/person-create-modal/person-create-modal.component';
import { PersonUpdateModalComponent } from '../../components/person-update-modal/person-update-modal.component';
import { AdminCreateModalComponent } from '../../components/admin-create-modal/admin-create-modal.component';
import { AdminUpdateModalComponent } from '../../components/admin-update-modal/admin-update-modal.component';
import { PlayerSpecModalComponent } from '../../components/player-spec-modal/player-spec-modal.component';
import { MessageService } from '../../../../core/services/message.service';
import { PeopleType, Parentesco, PersonData, UserData, CreatePersonPayload, UpdatePersonPayload, CreateAdminPayload, UpdateAdminPayload, CreatePlayerSpecPayload } from '../../services/users-http.service';

@Component({
  selector: 'app-users-page',
  imports: [
    UsersTableComponent,
    PersonCreateModalComponent,
    PersonUpdateModalComponent,
    AdminCreateModalComponent,
    AdminUpdateModalComponent,
    PlayerSpecModalComponent,
  ],
  templateUrl: './users-page.component.html',
})
export class UsersPageComponent implements OnInit {
  protected facade = inject(UsersFacade);
  private msg = inject(MessageService);

  ngOnInit(): void {
    this.facade.loadTabs();
  }
  vm = this.facade.vm;

  isPersonTab = computed(() => this.vm().activeTab !== 'admin');

  protected tabLabel = computed(() => {
    const s = this.vm();
    if (s.activeTab === 'admin') return this.msg.get('cr_lbl_administrators');
    const tab = s.tabs.find(t => t.code === s.activeTab);
    return tab?.label ?? this.msg.get('usr_lbl_users');
  });

  currentItems = computed<(PersonData | UserData)[]>(() => {
    const s = this.vm();
    if (s.activeTab === 'admin') {
      return s.admins.data;
    }
    return s.people[s.activeTab]?.data ?? [];
  });

  currentTotal = computed(() => {
    const s = this.vm();
    if (s.activeTab === 'admin') {
      return s.admins.total;
    }
    return s.people[s.activeTab]?.total ?? 0;
  });

  selectedPersonItem = computed<PersonData | null>(() => {
    const item = this.vm().selectedItem;
    if (item && 'first_name' in item) {
      return item as PersonData;
    }
    return null;
  });

  selectedAdminItem = computed<UserData | null>(() => {
    const item = this.vm().selectedItem;
    if (item && 'roles' in item) {
      return item as UserData;
    }
    return null;
  });

  selectedSpecUserId = computed<number | null>(() => this.vm().specUserId);
  selectedSpecData = computed(() => this.vm().specData);
  specLoading = computed(() => this.vm().specLoading);
  playerSpecs = computed(() => this.vm().playerSpecs);
  playerReps = computed(() => this.vm().playerReps);

  peopleTypes = computed<PeopleType[]>(() =>
    this.vm().tabs
      .filter(t => t.code !== 'admin')
      .map(t => ({ id: 0, code: t.code, description: t.label }))
  );

  parentescos = computed<Parentesco[]>(() => this.vm().parentescos);

  onFilter(term: string): void {
    this.facade.setSearchTerm(term);
  }

  onOpenCreateModal(): void {
    this.facade.openCreateModal();
  }

  onPageChange(page: number): void {
    this.facade.changePage(page);
  }

  onDelete(id: number): void {
    this.facade.deleteUser(id);
  }

  onRestore(id: number): void {
    this.facade.restoreUser(id);
  }

  onUpdate(item: PersonData | UserData): void {
    this.facade.openUpdateModal(item);
  }

  onCreatePerson(data: CreatePersonPayload): void {
    this.facade.createPerson(data);
  }

  onCreateAdmin(data: CreateAdminPayload): void {
    this.facade.createAdmin(data);
  }

  onUpdatePerson(data: UpdatePersonPayload): void {
    const item = this.selectedPersonItem();
    if (item) {
      this.facade.updateUser(item.id, data);
    }
  }

  onUpdateAdmin(data: UpdateAdminPayload): void {
    const item = this.selectedAdminItem();
    if (item) {
      this.facade.updateAdmin(item.id, data);
    }
  }

  onSpecAction(personId: number): void {
    this.facade.openSpecModal(personId);
  }

  onSaveSpec(data: CreatePlayerSpecPayload): void {
    this.facade.saveSpec(data);
  }

  onCloseSpec(): void {
    this.facade.closeSpecModal();
  }

  onCloseModals(): void {
    this.facade.closeModals();
  }
}
