import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { router } from '@inertiajs/core';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';

interface Client {
    id: number;
    name: string;
}

interface Props {
    clients: Client[];
}

interface FormData {
    [key: string]: string;
    client_id: string;
    app_name: string;
    name: string;
    value: string;
    client_identifier: string;
}

export default function Create({ clients }: Props) {
    const [form, setForm] = useState<FormData>({
        client_id: '',
        app_name: '',
        name: '',
        value: '',
        client_identifier: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        router.post(route('clientdetails.store'), form, {
            onSuccess: () => {
                toast.success('Client detail created successfully');
            },
            onError: (errors: Record<string, string>) => {
                Object.keys(errors).forEach((key) => {
                    toast.error(errors[key]);
                });
            },
        });
    };

    const handleChange = (field: keyof FormData, value: string) => {
        setForm((prev) => ({
            ...prev,
            [field]: value,
        }));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Create New Client Detail
                </h2>
            }
        >
            <Head title="Create Client Detail" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-6">
                                <h2 className="text-2xl font-semibold">
                                    Create New Client Detail
                                </h2>
                            </div>

                            <form
                                onSubmit={handleSubmit}
                                className="max-w-2xl space-y-6"
                            >
                                <div className="space-y-2">
                                    <label
                                        htmlFor="client_id"
                                        className="block text-sm font-medium"
                                    >
                                        Client
                                    </label>
                                    <Select
                                        value={form.client_id}
                                        onValueChange={(value: string) =>
                                            handleChange('client_id', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a client" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {clients.map((client) => (
                                                <SelectItem
                                                    key={client.id}
                                                    value={client.id.toString()}
                                                >
                                                    {client.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <label
                                        htmlFor="app_name"
                                        className="block text-sm font-medium"
                                    >
                                        App Name
                                    </label>
                                    <Input
                                        id="app_name"
                                        value={form.app_name}
                                        onChange={(
                                            e: React.ChangeEvent<HTMLInputElement>,
                                        ) =>
                                            handleChange(
                                                'app_name',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label
                                        htmlFor="name"
                                        className="block text-sm font-medium"
                                    >
                                        Name
                                    </label>
                                    <Input
                                        id="name"
                                        value={form.name}
                                        onChange={(
                                            e: React.ChangeEvent<HTMLInputElement>,
                                        ) =>
                                            handleChange('name', e.target.value)
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label
                                        htmlFor="value"
                                        className="block text-sm font-medium"
                                    >
                                        Value
                                    </label>
                                    <Input
                                        id="value"
                                        value={form.value}
                                        onChange={(
                                            e: React.ChangeEvent<HTMLInputElement>,
                                        ) =>
                                            handleChange(
                                                'value',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label
                                        htmlFor="client_identifier"
                                        className="block text-sm font-medium"
                                    >
                                        Client Identifier
                                    </label>
                                    <Input
                                        id="client_identifier"
                                        value={form.client_identifier}
                                        onChange={(
                                            e: React.ChangeEvent<HTMLInputElement>,
                                        ) =>
                                            handleChange(
                                                'client_identifier',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>

                                <div className="flex gap-4">
                                    <Button type="submit">
                                        Create Client Detail
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            router.visit(
                                                route('clientdetails.index'),
                                            )
                                        }
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
