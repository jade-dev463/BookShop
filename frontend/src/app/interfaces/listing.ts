import { DecimalPipe } from "@angular/common"
import { Book } from "./book"
import { BookCondition } from "../enums/book-condition"
import { StatusListing } from "../enums/status-listing"
import { User } from "./user"
import { Picture } from "./picture"
import { Category } from "./category"

export interface Listing {
    id: number
    title: string
    book_condition:BookCondition
    price: number
    statut: string
    publication_date: Date
    book: Book
    user: User
    picture: Picture | null
    category: Category
}

export interface ListingForm {
    title: string
    isbn: string
    book_condition:BookCondition
    price: number
    statut: StatusListing
    category: number
}
