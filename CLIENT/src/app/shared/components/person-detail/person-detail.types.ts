export interface PersonDetailModel {
  display_name: string;
  document_number: string;
  email: string;
  phone: string | null;
  address: string | null;
  type: string;
  associated_user: string;
  created_at: string;
  updated_at: string;
}
