import { defineCollection, z } from "astro:content";

const store = defineCollection({
  schema: ({ image }) =>
    z.object({
      title: z.string(),
      category: z.string(),
      price: z.string(),
      checkout: z.string(),
      returns: z.string().optional(),
      shipping: z.string().optional(),
      license: z.string(),
      description: z.string(),
      highlights: z.array(z.string()),
      thumbnail: z.object({
        url: image(),
        alt: z.string(),
      }),
      images: z
        .array(
          z.object({
            url: image(),
            alt: z.string().optional(),
          })
        )
        .optional(),
      testimonials: z
        .array(
          z.object({
            name: z.string(),
            date: z.string(),
            text: z.string(),
            img: z.string(), 
          })
        )
        .optional(),

      tags: z.array(z.string()),
    }),
});

const helpcenter = defineCollection({
  schema: z.object({
    title: z.string(),
    intro: z.string(),
  }),
});

const legal = defineCollection({
  schema: z.object({
    page: z.string(),
    pubDate: z.date(),
  }),
});

const posts = defineCollection({
  schema: ({ image }) =>
    z.object({
      title: z.string(),
      pubDate: z.date(),
      description: z.string(),
      author: z.string(),
      image: z.object({
        url: image(),
        alt: z.string(),
      }),
      tags: z.array(z.string()),
    }),
});

export const collections = {
store,
helpcenter,
legal,
posts,
};
