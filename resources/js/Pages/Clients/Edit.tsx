import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/core';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import { toast } from 'sonner';

interface Client {
    id: number;
    name: string;
    email: string;
    contact_number: string;
    client_identifier: string;
    referrer: string;
    active: boolean;
}

interface Props {
    client: Client;
}

interface FormData {
    [key: string]: string | boolean;
    name: string;
    email: string;
    contact_number: string;
    client_identifier: string;
    referrer: string;
    active: boolean;
}

export default function Edit({ client }: Props) {
    const [form, setForm] = useState<FormData>({
        name: client.name,
        email: client.email,
        contact_number: client.contact_number,
        client_identifier: client.client_identifier,
        referrer: client.referrer,
        active: client.active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        router.put(route('clients.update', client.id), form, {
            onSuccess: () => {
                toast.success('Client updated successfully');
            },
            onError: (errors: Record<string, string>) => {
                Object.keys(errors).forEach(key => {
                    toast.error(errors[key]);
                });
            },
        });
    };

    const handleChange = (field: keyof FormData, value: string | boolean) => {
        setForm(prev => ({
            ...prev,
            [field]: value
        }));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Edit Client</h2>}
        >
            <Head title="Edit Client" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-6">
                                <h2 className="text-2xl font-semibold">Edit Client</h2>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl">
                                <div className="space-y-2">
                                    <label htmlFor="name" className="block text-sm font-medium">
                                        Name
                                    </label>
                                    <Input
                                        id="name"
                                        value={form.name}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                            handleChange('name', e.target.value)
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="email" className="block text-sm font-medium">
                                        Email
                                    </label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={form.email}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                            handleChange('email', e.target.value)
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="contact_number" className="block text-sm font-medium">
                                        Contact Number
                                    </label>
                                    <Input
                                        id="contact_number"
                                        value={form.contact_number}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                            handleChange('contact_number', e.target.value)
                                        }
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="client_identifier" className="block text-sm font-medium">
                                        Client Identifier
                                    </label>
                                    <Input
                                        id="client_identifier"
                                        value={form.client_identifier}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                            handleChange('client_identifier', e.target.value)
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="referrer" className="block text-sm font-medium">
                                        Referrer
                                    </label>
                                    <Input
                                        id="referrer"
                                        value={form.referrer}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                            handleChange('referrer', e.target.value)
                                        }
                                        required
                                    />
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Switch
                                        id="active"
                                        checked={form.active}
                                        onCheckedChange={(checked: boolean) => handleChange('active', checked)}
                                    />
                                    <label htmlFor="active" className="text-sm font-medium">
                                        Active
                                    </label>
                                </div>

                                <div className="flex gap-4">
                                    <Button type="submit">
                                        Update Client
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => router.visit(route('clients.index'))}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
} 