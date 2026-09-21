import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Book } from '../interfaces/book';
import { Author } from '../interfaces/author';

import { User } from '../interfaces/user';
import { GoogleBook } from '../interfaces/google-book';
import { Category, NewCategory } from '../interfaces/category';
import { catchError, map, Observable, of, tap, throwError } from 'rxjs';
import { Listing, ListingForm } from '../interfaces/listing';
import { Discussion } from '../interfaces/discussion';
import { SendMessage } from '../interfaces/send-message';
import { Message } from '../interfaces/message';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root',
})
export class ApiService {
  private http = inject(HttpClient);
  private readonly url = environment.apiUrl

  getBooks() {
    return this.http.get<Book[]>(`${this.url}/books`);
  }
  
  searchBookByIsbn(isbn: string): Observable<GoogleBook | null> {
  if (!isbn) return of(null);

  const params = new HttpParams().set('isbn', isbn);

  return this.http
    .get<GoogleBook>(`${this.url}/books/search`, { params })
    .pipe(
      catchError(() => {
        return of(null);
      }),
    );
}

  getAuthor() {
    return this.http.get<Author[]>(`${this.url}/authors`);
  }

  getCategories() {
    return this.http.get<Category[]>(`${this.url}/categories`);
  }

  postCategory(data: any){
    return this.http.post<NewCategory>(`${this.url}/new/category`, data, {
      withCredentials: true
    })
  }

  getListings(): Observable<Listing[]> {
    return this.http.get<Listing[]>(`${this.url}/booklistings`).pipe(
      map(listings => listings.map(l => this.normalizeListing(l)))
    );
  }

  getListingById(id: number): Observable<Listing> {
    return this.http.get<Listing>(`${this.url}/listing/${id}`).pipe(
      map(listing => this.normalizeListing(listing))
    );
  }

  private normalizeListing(listing: Listing): Listing {
    if (!listing.picture) return listing;
    return {
      ...listing,
      picture: {
        frontCover: this.buildImageUrl(listing.picture.frontCover),
        backCover:  this.buildImageUrl(listing.picture.backCover),
      },
    };
  }

  private buildImageUrl(path?: string | null): string | null {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    return `${environment.uploadsUrl}${path.startsWith('/') ? '' : '/'}${path}`;
  }

  postListing(formData: FormData): Observable<Listing> {
    return this.http.post<Listing>(`${this.url}/new/listing`, formData, {
      withCredentials: true,
    });
  }

  updateListing(id: number, data: any) {
    return this.http.put(`${this.url}edit/listing/${id}`, data);
  }

  deleteListing(id: number) {
    return this.http.delete(`${this.url}/listing/${id}`);
  }
  getListingsByUser() {
    return this.http.get<Listing[]>(`${this.url}/listings/me`, {
      withCredentials: true,
    });
  }

  getUser() {
    return this.http.get<User[]>(`${this.url}/users`, {
      withCredentials: true
    });
  }

  getMyMessages() {
  return this.http.get<Discussion[]>(`${this.url}/conversations/me`,{
    withCredentials: true
  });
}

getConversation(userId: number, listingId: number) {
  return this.http.get<Message[]>(`${this.url}/conversation/${userId}/${listingId}`, {
    withCredentials:true
  });
}

sendMessage(userId: number, listingId: number, content: string) {
  return this.http.post<Message>(
    `${this.url}/conversation/${userId}/${listingId}`,
    { content } satisfies SendMessage,{
      withCredentials:true
    }
  );
}
}
