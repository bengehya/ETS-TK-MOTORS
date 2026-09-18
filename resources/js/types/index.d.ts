export type RoleName = 'BOSS_PRINCIPAL' | 'BOSS_SECONDAIRE' | 'EMPLOYE';

export interface User {
    id: number;
    name: string;
    email: string;
    role: RoleName;
    role_label: string;
    permissions: string[];
    email_verified_at?: string | null;
}

export interface Organization {
    id: number;
    name: string;
    slug: string;
}

export interface Brand {
    name: string;
    company: string;
    slogan: string;
    city: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    organization: Organization | null;
    brand: Brand;
    canRegister: boolean;
    flash: {
        status: string | null;
    };
};
